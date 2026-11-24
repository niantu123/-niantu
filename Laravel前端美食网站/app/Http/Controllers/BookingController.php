<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * 到店预约：只有店主开了「在线预约」的餐厅才能约
 */
class BookingController extends Controller
{
    /** 我的预约：分「即将到来」和「历史记录」两组 */
    public function index(Request $request): View
    {
        $bookings = $request->user()
            ->bookings()
            ->with('restaurant')
            ->get();

        $isUpcoming = fn (Booking $booking) => $booking->status !== Booking::STATUS_CANCELLED
            && $booking->book_date !== null
            && $booking->book_date->endOfDay()->isFuture();

        $upcoming = $bookings->filter($isUpcoming)->sortBy(fn (Booking $b) => $b->book_date->timestamp);
        $past = $bookings->reject($isUpcoming)->sortByDesc(fn (Booking $b) => $b->book_date?->timestamp ?? 0);

        return view('account.bookings', compact('upcoming', 'past'));
    }

    /** 提交预约 */
    public function store(Request $request, Restaurant $restaurant): RedirectResponse
    {
        if (! $restaurant->accepts_booking) {
            return back()->with('error', '「'.$restaurant->name.'」暂时不接受在线预约，建议直接打个电话问问。');
        }

        $data = $request->validate([
            'book_date' => ['required', 'date'],
            'book_time' => ['required', 'string', Rule::in(Booking::TIME_SLOTS)],
            'party_size' => ['required', 'integer', 'between:1,'.Booking::MAX_PARTY],
            'contact_name' => ['required', 'string', 'min:2', 'max:30'],
            'contact_phone' => ['required', 'regex:/^1[3-9]\d{9}$/'],
            'note' => ['nullable', 'string', 'max:200'],
        ], [
            'book_date.required' => '请选择到店日期。',
            'book_date.date' => '日期格式不对。',
            'book_time.required' => '请选择到店时段。',
            'book_time.in' => '这个时段店里不接单，换一个吧。',
            'party_size.required' => '请填写用餐人数。',
            'party_size.between' => '人数填 1 到 ' . Booking::MAX_PARTY . ' 位，人更多请直接打电话。',
            'contact_name.required' => '请填写到店联系人。',
            'contact_name.min' => '联系人姓名至少 2 个字。',
            'contact_phone.required' => '请填写手机号，店家要联系你确认。',
            'contact_phone.regex' => '手机号看起来不对，填 11 位大陆手机号。',
            'note.max' => '备注最多 200 个字。',
        ]);

        $date = Carbon::parse($data['book_date'])->startOfDay();
        $today = Carbon::today();

        if ($date->lt($today)) {
            throw ValidationException::withMessages(['book_date' => '预约日期不能选今天以前的日子。']);
        }

        if ($date->gt($today->copy()->addDays(Booking::DAYS_AHEAD))) {
            throw ValidationException::withMessages([
                'book_date' => '最多只能提前 '.Booking::DAYS_AHEAD.' 天预约。',
            ]);
        }

        $duplicated = Booking::query()
            ->where('user_id', $request->user()->id)
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('book_date', $date)
            ->where('book_time', $data['book_time'])
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->exists();

        if ($duplicated) {
            throw ValidationException::withMessages([
                'book_time' => '这天这个时段你已经约过了，换个时间，或者去「我的预约」里改。',
            ]);
        }

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'restaurant_id' => $restaurant->id,
            'book_date' => $date,
            'book_time' => $data['book_time'],
            'party_size' => (int) $data['party_size'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'note' => $data['note'] ?? null,
            'status' => Booking::STATUS_PENDING,
        ]);

        return back()->with(
            'status',
            '预约已提交：'.$date->format('n 月 j 日').' '.$booking->book_time.' · '.$booking->party_size
            .' 位，预约号 '.$booking->number.'。店家确认后会联系你。'
        );
    }

    /** 取消预约 */
    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if (! $booking->cancellable) {
            return redirect()->route('bookings.index')
                ->with('error', '这条预约已经不能在线取消了，直接打电话跟店里说一声吧。');
        }

        $booking->update(['status' => Booking::STATUS_CANCELLED]);

        return redirect()->route('bookings.index')
            ->with('status', '预约已取消，'.$booking->restaurant?->name.' 的座位已释放。');
    }
}
