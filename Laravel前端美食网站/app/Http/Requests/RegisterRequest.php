<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 注册表单校验规则
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:20',
                'regex:/^[\p{Han}A-Za-z0-9_]+$/u',
                'unique:users,name',
            ],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:32', 'confirmed'],
            'role' => ['required', 'string', Rule::in(User::REGISTERABLE_ROLES)],
            'agree' => ['accepted'],
        ];
    }

    /**
     * 中文提示语
     */
    public function messages(): array
    {
        return [
            'name.required' => '请填写账号。',
            'name.min' => '账号至少 2 个字。',
            'name.max' => '账号最多 20 个字。',
            'name.regex' => '账号只能用中文、字母、数字和下划线。',
            'name.unique' => '这个账号已被占用，换一个吧。',
            'email.required' => '请填写邮箱。',
            'email.email' => '邮箱格式不正确。',
            'email.unique' => '这个邮箱已经注册过了，直接登录吧。',
            'password.required' => '请设置密码。',
            'password.min' => '密码至少 6 位。',
            'password.max' => '密码最多 32 位。',
            'password.confirmed' => '两次输入的密码不一致。',
            'role.required' => '请选择注册身份。',
            'role.in' => '身份只能选择普通用户、餐厅管理员或食材供应商。',
            'agree.accepted' => '请先阅读并同意用户协议。',
        ];
    }
}
