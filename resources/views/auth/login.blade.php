@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div style="max-width: 420px; margin: 2rem auto;">
    <div class="card">
        <h1 style="margin-bottom: .5rem;">Sign in</h1>
        <p class="muted" style="margin-bottom: 1.5rem;">Access the Kigali Route Management Platform</p>

        @if($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required>
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: .5rem;">
                <input type="checkbox" name="remember" id="remember" style="width: auto;">
                <label for="remember" style="margin: 0; font-weight: 500;">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign in</button>
        </form>

        <div style="margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid var(--border);">
            <p class="muted" style="margin-bottom: .5rem;">Demo accounts (password: <code>password</code>)</p>
            <ul class="muted" style="font-size: .85rem; padding-left: 1.25rem;">
                <li>Driver: jean@kigaliroutes.rw</li>
                <li>Passenger: passenger@kigaliroutes.rw</li>
                <li>Admin: admin@kigaliroutes.rw</li>
            </ul>
        </div>
    </div>
</div>
@endsection
