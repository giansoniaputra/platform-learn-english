@extends('layouts.dashboard')

@section('title', 'Kelola Pengguna')

@section('content')
  <div class="dashboard-topbar" style="margin-bottom:22px">
    <h2 style="font-size:19px">Kelola Pengguna</h2>
  </div>

  <div class="card">
    <div class="section-label" style="margin:0 0 14px"><div class="eyebrow">Buat akun baru</div><hr></div>
    <form method="POST" action="{{ route('dashboard.users.store') }}">
      @csrf

      <div class="field">
        <label for="name">Nama</label>
        <input id="name" name="name" type="text" value="{{ old('name') }}" required>
        @error('name') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" inputmode="email" value="{{ old('email') }}" required>
        @error('email') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div class="field">
        <label for="password">Kata sandi</label>
        <input id="password" name="password" type="password" minlength="8" required>
        @error('password') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <div class="field">
        <label for="role">Role</label>
        <select id="role" name="role" required>
          <option value="member" @selected(old('role', 'member') === 'member')>Member</option>
          <option value="admin" @selected(old('role') === 'admin')>Admin</option>
        </select>
        @error('role') <p class="form-error">{{ $message }}</p> @enderror
      </div>

      <button class="btn" type="submit" style="width:auto;padding:13px 22px">Buat Akun</button>
    </form>
  </div>

  <div class="section-label" style="margin-top:30px"><div class="eyebrow">Semua Pengguna ({{ $users->count() }})</div><hr></div>
  @foreach ($users as $u)
    <div class="data-row">
      <div class="meta">
        <b>{{ $u->name }}</b>
        <span>{{ $u->email }}</span>
      </div>
      <span class="keyword-active-badge">{{ strtoupper($u->role) }}</span>
    </div>
  @endforeach
@endsection
