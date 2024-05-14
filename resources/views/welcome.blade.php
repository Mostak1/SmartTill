@extends('layouts.home')
@section('title', config('app.name', 'Lacuna ERP'))

@section('content')
    <style type="text/css">
        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
            margin-top: 10%;
        }

        .max-w-screen-sm {
            width: 550px;
            margin: 30px auto;
        }

        .title {
            font-size: 84px;
        }

        .tagline {
            font-size: 25px;
            font-weight: 300;
            text-align: center;
        }
        .tagline1 {
            font-size: 30px;
            font-weight: 300;
        }

        @media only screen and (max-width: 600px) {
            .title {
                font-size: 38px;
            }

            .max-w-screen-sm {
                width: 350px;
                margin: 0 auto;
            }

            .tagline {
                font-size: 18px;
            }
        }
    </style>
   
    <p class="tagline">
        {{-- {{ env('APP_TITLE', '') }} --}}
        <img src="{{asset('uploads/business_logos/logo.png')}}" alt="">
    </p>
    <div class="max-w-screen-sm">

        <div class="login-form col-md-12 col-xs-12 right-col-content">
            <p class="form-header tagline1 text-white">@lang('lang_v1.login')</p>
            <form method="POST" action="{{ route('login') }}" id="login-form">
                {{ csrf_field() }}
                <div class="form-group has-feedback {{ $errors->has('username') ? ' has-error' : '' }}">
                    @php
                        $username = old('username');
                        $password = null;
                        if (config('app.env') == 'demo') {
                            $username = 'admin';
                            $password = '123456';

                            $demo_types = [
                                'all_in_one' => 'admin',
                                'super_market' => 'admin',
                                'pharmacy' => 'admin-pharmacy',
                                'electronics' => 'admin-electronics',
                                'services' => 'admin-services',
                                'restaurant' => 'admin-restaurant',
                                'superadmin' => 'superadmin',
                                'woocommerce' => 'woocommerce_user',
                                'essentials' => 'admin-essentials',
                                'manufacturing' => 'manufacturer-demo',
                            ];

                            if (!empty($_GET['demo_type']) && array_key_exists($_GET['demo_type'], $demo_types)) {
                                $username = $demo_types[$_GET['demo_type']];
                            }
                        }
                    @endphp
                    <input id="username" type="text" class="form-control" name="username" value="{{ $username }}"
                        required autofocus placeholder="@lang('lang_v1.username')">
                    <span class="fa fa-user form-control-feedback"></span>
                    @if ($errors->has('username'))
                        <span class="help-block">
                            <strong>{{ $errors->first('username') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group has-feedback {{ $errors->has('password') ? ' has-error' : '' }}">
                    <input id="password" type="password" class="form-control" name="password" value="{{ $password }}"
                        required placeholder="@lang('lang_v1.password')">
                    <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    @if ($errors->has('password'))
                        <span class="help-block">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="form-group">
                    <div class="checkbox icheck">
                        <label>
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            @lang('lang_v1.remember_me')
                        </label>
                    </div>
                </div>
                <br>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-flat btn-login">@lang('lang_v1.login')</button>
                    @if (config('app.env') != 'demo')
                        <a href="{{ route('password.request') }}" class="pull-right">
                            @lang('lang_v1.forgot_your_password')
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
    <div class="title flex-center" style="font-weight: 600 !important;">
        {{-- {{ config('app.name', 'ultimatePOS') }} --}}
    </div>
@endsection
