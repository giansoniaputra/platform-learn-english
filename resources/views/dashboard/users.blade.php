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
        @unless ($u->isAdmin())
          <span id="token-usage-{{ $u->id }}">{{ number_format($u->tokens_used) }}/{{ number_format($u->token_limit) }} token</span>
        @endunless
      </div>
      <div class="actions">
        <span class="keyword-active-badge">{{ strtoupper($u->role) }}</span>
        @unless ($u->isAdmin())
          <button type="button" class="token-grant-btn" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name }}">+ Token</button>
        @endunless
      </div>
    </div>
  @endforeach

  <div class="cek-modal-overlay" id="token-modal-overlay" style="display:none">
    <div class="card cek-modal">
      <button type="button" class="cek-modal-close" id="token-modal-close" aria-label="Tutup">×</button>
      <h3 id="token-modal-title" style="font-size:16px;margin-bottom:14px"></h3>
      <div class="field">
        <label for="token-modal-amount">Jumlah (USD)</label>
        <input type="number" id="token-modal-amount" step="0.01" min="0.01" placeholder="mis. 0.5">
      </div>
      <p class="ai-status" id="token-modal-status" aria-live="polite"></p>
      <button class="btn" type="button" id="token-modal-submit" style="width:auto;padding:12px 20px">Tambahkan</button>
    </div>
  </div>

  <script>
    const tokenModalOverlay = document.getElementById('token-modal-overlay');
    const tokenModalTitle = document.getElementById('token-modal-title');
    const tokenModalAmount = document.getElementById('token-modal-amount');
    const tokenModalStatus = document.getElementById('token-modal-status');
    const tokenCsrfToken = document.querySelector('meta[name="csrf-token"]').content;

    document.querySelectorAll('.token-grant-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        tokenModalOverlay.dataset.userId = btn.dataset.userId;
        tokenModalTitle.textContent = 'Tambah token untuk ' + btn.dataset.userName;
        tokenModalAmount.value = '';
        tokenModalStatus.textContent = '';
        tokenModalOverlay.style.display = 'flex';
      });
    });

    document.getElementById('token-modal-close').addEventListener('click', () => {
      tokenModalOverlay.style.display = 'none';
    });

    tokenModalOverlay.addEventListener('click', (e) => {
      if (e.target === tokenModalOverlay) tokenModalOverlay.style.display = 'none';
    });

    document.getElementById('token-modal-submit').addEventListener('click', async () => {
      const userId = tokenModalOverlay.dataset.userId;
      const amount = parseFloat(tokenModalAmount.value);

      if (!amount || amount <= 0) {
        tokenModalStatus.textContent = 'Masukkan jumlah dolar yang valid.';
        return;
      }

      tokenModalStatus.textContent = 'Menyimpan...';

      try {
        const res = await fetch(`/dashboard/users/${userId}/tokens`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': tokenCsrfToken,
          },
          body: JSON.stringify({ amount_usd: amount }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Gagal menambah token.');

        const usageEl = document.getElementById('token-usage-' + userId);
        if (usageEl) {
          usageEl.textContent = new Intl.NumberFormat('id-ID').format(data.tokens_used)
            + '/' + new Intl.NumberFormat('id-ID').format(data.token_limit) + ' token';
        }

        tokenModalOverlay.style.display = 'none';
      } catch (err) {
        tokenModalStatus.textContent = err.message;
      }
    });
  </script>
@endsection
