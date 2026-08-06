@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
  <form class="keyword-form" method="POST" action="{{ route('dashboard.keywords.store') }}" id="keyword-form">
    @csrf
    <input type="text" name="term" placeholder="Ketik kunci, mis. &quot;pekerjaan&quot;" required
      value="{{ old('term') }}">
    <button class="btn" type="submit" id="keyword-submit" style="width:auto;padding:13px 22px">Buat</button>
  </form>

  @if ($keywords->isNotEmpty())
    <div class="field keyword-select">
      <label for="keyword-picker">Kunci sebelumnya</label>
      <select id="keyword-picker" onchange="if(this.value) window.location = '{{ route('dashboard') }}?keyword=' + this.value">
        <option value="">— pilih kunci —</option>
        @foreach ($keywords as $k)
          <option value="{{ $k->slug }}" @selected($selected?->id === $k->id)>
            {{ $k->term }} @if ($k->is_active) (aktif) @endif
          </option>
        @endforeach
      </select>
    </div>
  @endif

  @if (! $selected)
    <div class="card">
      <p style="color:var(--ink-soft);font-size:14px">Belum ada kunci. Ketik satu di atas (mis. "pekerjaan", "restoran",
        "perjalanan") untuk membuat kosakata & percakapan pertamamu lewat AI.</p>
    </div>
  @else
    <div class="dashboard-topbar" style="margin:26px 0 16px">
      <h2 style="font-size:19px">{{ $selected->term }}</h2>
      @if ($selected->is_active)
        <span class="keyword-active-badge">Aktif di aplikasi</span>
      @else
        <form method="POST" action="{{ route('dashboard.keywords.activate', $selected) }}">
          @csrf
          <button class="dashboard-logout" type="submit">Jadikan aktif</button>
        </form>
      @endif
    </div>

    <div class="section-label"><div class="eyebrow">Kosakata ({{ $words->count() }})</div><hr></div>
    <div id="word-cards">
      @foreach ($words as $word)
        @include('dashboard.partials.word-card', ['word' => $word])
      @endforeach
    </div>
    <button class="btn-add-row" type="button" id="load-more-words">+ Muat 10 kata lagi</button>
    <p class="ai-status" id="words-status"></p>

    <div class="section-label" style="margin-top:30px"><div class="eyebrow">Percakapan ({{ $topics->count() }})</div><hr></div>
    <div id="topic-cards">
      @foreach ($topics as $i => $topic)
        @include('dashboard.partials.topic-card', ['topic' => $topic, 'caseNumber' => $i + 1])
      @endforeach
    </div>
    <button class="btn-add-row" type="button" id="load-more-topics">+ Muat kasus lagi</button>
    <p class="ai-status" id="topics-status"></p>

    <div class="section-label" style="margin-top:30px"><div class="eyebrow">Latihan</div><hr></div>
    <div class="card">
      <p style="font-size:13px;color:var(--ink-soft);line-height:1.6">
        Diturunkan langsung dari kosakata di atas (tanpa panggilan AI baru — jadi instan &amp; gratis). Klik tombol di
        bawah tiap kali menambah kosakata baru supaya latihannya ikut lengkap.
      </p>
      <div class="exercise-counts">
        <div><b>{{ $exerciseCounts['arrange'] ?? 0 }}</b><span>Susun kata</span></div>
        <div><b>{{ $exerciseCounts['fill_blank'] ?? 0 }}</b><span>Lengkapi kalimat</span></div>
        <div><b>{{ $exerciseCounts['match_meaning'] ?? 0 }}</b><span>Padankan arti</span></div>
        <div><b>{{ $exerciseCounts['listening'] ?? 0 }}</b><span>Menyimak</span></div>
      </div>
      <form method="POST" action="{{ route('dashboard.exercises.generate', $selected) }}" style="margin-top:14px">
        @csrf
        <button class="btn" type="submit" style="width:auto;padding:12px 20px">Buat/perbarui latihan</button>
      </form>
    </div>
  @endif

  <script>
    document.getElementById("keyword-form").addEventListener("submit", (e) => {
      const btn = document.getElementById("keyword-submit");
      btn.disabled = true;
      btn.textContent = "Membuat…";
    });

    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function escapeHtml(str) {
      const div = document.createElement("div");
      div.textContent = str ?? "";
      return div.innerHTML;
    }

    function wordCardHtml(w) {
      const verbs = (w.verb1 || w.verb2 || w.verb3)
        ? `<div class="verbs">V1 ${escapeHtml(w.verb1 || "-")} · V2 ${escapeHtml(w.verb2 || "-")} · V3 ${escapeHtml(w.verb3 || "-")}</div>`
        : "";
      return `
      <div class="gen-word-card" data-id="${w.id}">
        <button class="del" type="button" aria-label="Hapus kata">×</button>
        <div class="head">
          <span class="en">${escapeHtml(w.en)}</span>
          <span class="ipa">${escapeHtml(w.ipa)}</span>
          <span class="pos">${escapeHtml(w.pos)}</span>
        </div>
        <div class="id">${escapeHtml(w.translation)}</div>
        ${w.example ? `<p class="ex">${escapeHtml(w.example)}</p>` : ""}
        ${w.example && w.example_translation ? `<p class="ex-id">${escapeHtml(w.example_translation)}</p>` : ""}
        ${verbs}
      </div>`;
    }

    function topicCardHtml(t, caseNumber) {
      const lines = (t.lines || []).map(l => `
        <div class="line">
          <b>${l.me ? "Kamu" : escapeHtml(t.partner)}</b>
          ${escapeHtml(l.en)} <span style="color:var(--ink-soft)">— ${escapeHtml(l.id)}</span>
        </div>`).join("");
      const keys = (t.keys || []).map(k => `<div>• <b>${escapeHtml(k[0])}</b> — ${escapeHtml(k[1])}</div>`).join("");
      return `
      <div class="gen-topic-card" data-id="${t.id}">
        <div class="head" data-toggle>
          <div>
            <b>Kasus ${caseNumber} · ${escapeHtml(t.title)}</b>
            <span>${escapeHtml(t.blurb)} — lawan bicara: ${escapeHtml(t.partner)}</span>
          </div>
          <button class="del" type="button" aria-label="Hapus kasus">×</button>
        </div>
        <div class="body">
          ${lines}
          <div class="keys">${keys}</div>
        </div>
      </div>`;
    }

    function bindWordCard(el) {
      el.querySelector(".del").addEventListener("click", async () => {
        if (!confirm("Hapus kata ini?")) return;
        const res = await fetch(`/dashboard/words/${el.dataset.id}`, {
          method: "DELETE",
          headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
        });
        if (res.ok) el.remove();
      });
    }

    function bindTopicCard(el) {
      el.querySelector("[data-toggle]").addEventListener("click", (e) => {
        if (e.target.closest(".del")) return;
        el.classList.toggle("open");
      });
      el.querySelector(".del").addEventListener("click", async (e) => {
        e.stopPropagation();
        if (!confirm("Hapus kasus percakapan ini?")) return;
        const res = await fetch(`/dashboard/topics/${el.dataset.id}`, {
          method: "DELETE",
          headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
        });
        if (res.ok) el.remove();
      });
    }

    document.querySelectorAll(".gen-word-card").forEach(bindWordCard);
    document.querySelectorAll(".gen-topic-card").forEach(bindTopicCard);

    const keywordSlug = @json($selected?->slug);

    const loadMoreWordsBtn = document.getElementById("load-more-words");
    if (loadMoreWordsBtn) {
      loadMoreWordsBtn.addEventListener("click", async () => {
        const statusEl = document.getElementById("words-status");
        loadMoreWordsBtn.disabled = true;
        statusEl.textContent = "Meminta 10 kata baru ke AI…";
        try {
          const res = await fetch(`/dashboard/keywords/${keywordSlug}/words`, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.error || "Gagal memuat kata baru.");
          const container = document.getElementById("word-cards");
          data.words.forEach(w => {
            container.insertAdjacentHTML("beforeend", wordCardHtml(w));
            bindWordCard(container.lastElementChild);
          });
          statusEl.textContent = "";
        } catch (err) {
          statusEl.textContent = err.message;
        } finally {
          loadMoreWordsBtn.disabled = false;
        }
      });
    }

    const loadMoreTopicsBtn = document.getElementById("load-more-topics");
    if (loadMoreTopicsBtn) {
      loadMoreTopicsBtn.addEventListener("click", async () => {
        const statusEl = document.getElementById("topics-status");
        loadMoreTopicsBtn.disabled = true;
        statusEl.textContent = "Meminta 1 kasus percakapan baru ke AI…";
        try {
          const res = await fetch(`/dashboard/keywords/${keywordSlug}/topics`, {
            method: "POST",
            headers: { "X-CSRF-TOKEN": csrfToken, "Accept": "application/json" },
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.error || "Gagal memuat kasus baru.");
          const container = document.getElementById("topic-cards");
          const caseNumber = container.children.length + 1;
          container.insertAdjacentHTML("beforeend", topicCardHtml(data.topic, caseNumber));
          bindTopicCard(container.lastElementChild);
          statusEl.textContent = "";
        } catch (err) {
          statusEl.textContent = err.message;
        } finally {
          loadMoreTopicsBtn.disabled = false;
        }
      });
    }
  </script>
@endsection
