<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Sepuluh — belajar bahasa Inggris tiap hari</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;1,400&family=Space+Mono:wght@400;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/style.css') }}?v={{ filemtime(public_path('assets/style.css')) }}">
</head>

<body>
  <div class="phone">

    <!-- ============ BERANDA ============ -->
    <section class="screen is-on" id="beranda">
      <header class="topbar">
        <div>
          <div class="day">{{ now()->locale('id')->translatedFormat('l, j F') }}</div>
          <h2>Halo, {{ explode(' ', auth()->user()->name)[0] }}.</h2>
        </div>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="streak"><b>11</b><small>hari</small></div>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="dashboard-logout" type="submit" style="padding:7px 10px">Keluar</button>
          </form>
        </div>
      </header>

      <div class="scroll">
        @if ($activeKeyword)
          <div class="card progress-card">
            <div class="progress-head">
              <div class="count"><span id="home-count">0</span><small>/{{ $words->count() }} kata</small></div>
              <div class="eyebrow">{{ $activeKeyword->term }}</div>
            </div>
            <div class="bar"><i id="home-bar"></i></div>
            <p class="bar-note" id="home-note">Belum ada kata yang ditandai hari ini.</p>
          </div>

          <div class="section-label">
            <div class="eyebrow">Lanjutkan</div>
            <hr>
          </div>

          <button class="jump" data-go="kosakata">
            <div class="num">01</div>
            <div class="txt"><b>Kosakata: {{ $activeKeyword->term }}</b><span>{{ $words->count() }} kata</span></div>
            <div class="arrow">→</div>
          </button>

          <button class="jump" data-go="percakapan">
            <div class="num">02</div>
            <div class="txt"><b>Percakapan: {{ $activeKeyword->term }}</b><span>{{ $topics->count() }} kasus dialog</span></div>
            <div class="arrow">→</div>
          </button>
        @else
          <div class="card">
            <h3>Belum ada konten aktif</h3>
            <p style="font-size:14px;color:var(--ink-soft);margin-top:6px">Buka dashboard untuk mengetik kunci (mis.
              "pekerjaan") dan buat kosakata & percakapan pertamamu lewat AI.</p>
          </div>
        @endif

        <div class="section-label">
          <div class="eyebrow">Kelola</div>
          <hr>
        </div>

        <a class="jump" href="{{ route('dashboard') }}">
          <div class="num">⚙</div>
          <div class="txt"><b>Kelola kosakata & percakapan</b><span>Buka dashboard admin</span></div>
          <div class="arrow">→</div>
        </a>
      </div>
    </section>

    <!-- ============ KOSAKATA ============ -->
    <section class="screen" id="kosakata">
      <header class="topbar">
        <div>
          <div class="day">{{ $activeKeyword->term ?? 'Belum ada kunci aktif' }}</div>
          <h2>Kosakata</h2>
        </div>
      </header>

      <div class="scroll">
        <div class="card progress-card">
          <div class="progress-head">
            <div class="count"><span id="voc-count">0</span><small>/10 dikuasai</small></div>
            <div class="eyebrow">Progres</div>
          </div>
          <div class="bar"><i id="voc-bar"></i></div>
          <p class="bar-note">Ketuk kotak di kiri kata kalau kamu sudah hafal.</p>
        </div>
        <div id="word-list"></div>
      </div>
    </section>

    <!-- ============ PERCAKAPAN (daftar) ============ -->
    <section class="screen" id="percakapan">
      <header class="topbar">
        <div>
          <div class="day">Latihan harian</div>
          <h2>Percakapan</h2>
        </div>
      </header>
      <div class="scroll" id="topic-list"></div>
    </section>

    <!-- ============ DIALOG ============ -->
    <section class="screen" id="dialog">
      <div class="detail-bar">
        <button class="back" id="btn-back" aria-label="Kembali ke daftar percakapan">←</button>
        <div class="t"><b id="dlg-title"></b><span id="dlg-day"></span></div>
      </div>
      <div class="scroll" id="dlg-body"></div>
    </section>

    <!-- ============ LATIHAN ============ -->
    <section class="screen" id="latihan">
      <header class="topbar">
        <div>
          <div class="day">{{ $activeKeyword->term ?? 'Belum ada kunci aktif' }}</div>
          <h2>Latihan</h2>
        </div>
      </header>
      <div class="scroll" id="latihan-body"></div>
    </section>

    <!-- ============ NAV ============ -->
    <nav class="tabbar" id="tabbar">
      <button data-go="beranda" class="on">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 10.5 12 4l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" />
        </svg>
        Beranda
      </button>
      <button data-go="kosakata">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 4h7a2 2 0 0 1 2 2v14a2 2 0 0 0-2-2H4z" />
          <path d="M20 4h-7a2 2 0 0 0-2 2v14a2 2 0 0 1 2-2h7z" />
        </svg>
        Kosakata
      </button>
      <button data-go="percakapan">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 14a2 2 0 0 1-2 2H8l-4 4V6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z" />
          <path d="M8 9h8M8 12h5" />
        </svg>
        Percakapan
      </button>
      <button data-go="latihan">
        <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 11l3 3L22 4" />
          <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
        </svg>
        Latihan
      </button>
    </nav>
  </div>

  <script>
    /* ---------------- data (dari dashboard admin) ---------------- */
    const WORDS = @json($words);
    const TOPICS = @json($topics);
    const EXERCISES = @json($exercises);

    /* ---------------- state ---------------- */
    const mastered = new Set();
    let showAllTrans = false;
    let quizInProgress = false;
    const aiHistory = {};

    /* ---------------- navigation ---------------- */
    const screens = document.querySelectorAll(".screen");
    const tabbar = document.getElementById("tabbar");

    function show(id, { tab = id } = {}) {
      screens.forEach(s => s.classList.toggle("is-on", s.id === id));
      tabbar.querySelectorAll("button").forEach(b =>
        b.classList.toggle("on", b.dataset.go === tab)
      );
      const sc = document.querySelector("#" + id + " .scroll");
      if (sc) sc.scrollTop = 0;
    }

    document.querySelectorAll("[data-go]").forEach(el =>
      el.addEventListener("click", () => {
        const target = el.dataset.go;
        const current = document.querySelector(".screen.is-on")?.id;

        if (current === "latihan" && quizInProgress) {
          if (!confirm("Anda yakin ingin keluar? Semua progres latihan akan direset.")) return;
          quizInProgress = false;
        }

        if (target === "latihan") {
          show(target);
          showLatihanMenu();
          return;
        }

        show(target);
      })
    );
    document.getElementById("btn-back").addEventListener("click", () => show("percakapan"));

    /* ---------------- text-to-speech (OpenAI TTS) ---------------- */
    const ttsCache = new Map();

    async function speak(text, btn, attempt = 1) {
      if (!text) return;
      if (btn) { btn.disabled = true; btn.classList.add("loading"); btn.classList.remove("error"); }
      try {
        let url = ttsCache.get(text);
        if (!url) {
          const res = await fetch("/api/tts", {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ text }),
          });
          if (!res.ok) throw new Error("tts failed");
          const blob = await res.blob();
          url = URL.createObjectURL(blob);
          ttsCache.set(text, url);
        }
        await new Audio(url).play();
      } catch (err) {
        // transien (rate limit / jaringan) — coba sekali lagi sebelum menyerah
        if (attempt < 2) {
          if (btn) { btn.disabled = false; btn.classList.remove("loading"); }
          return speak(text, btn, attempt + 1);
        }
        if (btn) {
          btn.classList.add("error");
          setTimeout(() => btn.classList.remove("error"), 1500);
        }
      } finally {
        if (btn) { btn.disabled = false; btn.classList.remove("loading"); }
      }
    }

    function speakBtnHtml(extraClass = "") {
      return `<button type="button" class="speak-btn ${extraClass}" aria-label="Dengarkan pengucapan">🔊</button>`;
    }

    /* ---------------- vocabulary ---------------- */
    const list = document.getElementById("word-list");

    if (WORDS.length === 0) {
      list.innerHTML = `<div class="card"><p style="font-size:14px;color:var(--ink-soft)">Belum ada kosakata. Buka dashboard untuk generate kosakata pertamamu.</p></div>`;
    }

    WORDS.forEach((w, i) => {
      const el = document.createElement("article");
      el.className = "word";
      const verbs = (w.verb1 || w.verb2 || w.verb3)
        ? `<p class="word-ex" style="font-style:normal;border:none;padding-left:0;margin-top:6px;color:var(--marker-deep)">V1 ${w.verb1 || "-"} · V2 ${w.verb2 || "-"} · V3 ${w.verb3 || "-"}</p>`
        : "";
      el.innerHTML = `
    <button class="tick" aria-pressed="false" aria-label="Tandai '${w.en}' sudah hafal">✓</button>
    <div class="word-body">
      <div class="word-head">
        <div class="word-en"><span class="mark">${w.en}</span></div>
        ${speakBtnHtml()}
        <div class="ipa">${w.ipa}</div>
        <div class="pos">${w.pos}</div>
      </div>
      <div class="word-id">${w.id}</div>
      <p class="word-ex">${w.ex}${w.ex ? speakBtnHtml("speak-btn-inline") : ""}</p>
      ${w.ex && w.ex_id ? `<p class="word-ex-id">${w.ex_id}</p>` : ""}
      ${verbs}
    </div>`;
      el.querySelector(".tick").addEventListener("click", () => {
        const on = !mastered.has(i);
        on ? mastered.add(i) : mastered.delete(i);
        el.classList.toggle("done", on);
        el.querySelector(".tick").setAttribute("aria-pressed", String(on));
        updateProgress();
      });
      const wordSpeakBtns = el.querySelectorAll(".speak-btn");
      wordSpeakBtns[0].addEventListener("click", (e) => { e.stopPropagation(); speak(w.en, wordSpeakBtns[0]); });
      if (wordSpeakBtns[1]) {
        wordSpeakBtns[1].addEventListener("click", (e) => { e.stopPropagation(); speak(w.ex, wordSpeakBtns[1]); });
      }
      list.appendChild(el);
    });

    function updateProgress() {
      const n = mastered.size, pct = n / WORDS.length * 100;
      document.getElementById("voc-count").textContent = n;
      document.getElementById("home-count").textContent = n;
      document.getElementById("voc-bar").style.width = pct + "%";
      document.getElementById("home-bar").style.width = pct + "%";
      const note = document.getElementById("home-note");
      note.textContent =
        n === 0 ? "Belum ada kata yang ditandai hari ini."
          : n < WORDS.length ? `Tinggal ${WORDS.length - n} kata lagi sebelum latihan percakapan.`
            : "Sepuluh kata selesai. Latihan percakapan menunggu.";
    }
    updateProgress();

    /* ---------------- conversation list ---------------- */
    const tl = document.getElementById("topic-list");

    if (TOPICS.length === 0) {
      tl.innerHTML = `<div class="card"><p style="font-size:14px;color:var(--ink-soft)">Belum ada percakapan. Buka dashboard untuk generate kasus percakapan pertamamu.</p></div>`;
    }

    TOPICS.forEach(t => {
      const b = document.createElement("button");
      b.className = "topic";
      b.innerHTML = `
    <div style="flex:1;min-width:0">
      <span class="badge today">${t.day}</span>
      <h3>${t.title}</h3>
      <p>${t.blurb}</p>
    </div>
    <div class="arrow" style="color:var(--ink-soft);font-size:19px">→</div>`;
      b.addEventListener("click", () => openTopic(t));
      tl.appendChild(b);
    });

    /* ---------------- AI conversation partner ---------------- */
    function escapeHtml(str) {
      const div = document.createElement("div");
      div.textContent = str;
      return div.innerHTML;
    }

    function renderAiLine(container, t, role, text, opts = {}) {
      const d = document.createElement("div");
      d.className = "line" + (role === "user" ? " me" : "");
      const who = role === "user" ? "Kamu" : t.partner;
      d.innerHTML = `
        <div class="who">${escapeHtml(who)}</div>
        <div class="bubble"><p>${escapeHtml(text)}${speakBtnHtml("speak-btn-inline")}</p>${opts.translation ? `<div class="trans">${escapeHtml(opts.translation)}</div>` : ""}</div>`;
      if (opts.translation) {
        d.querySelector(".bubble").addEventListener("click", () => d.classList.toggle("show"));
      }
      const aiSpeakBtn = d.querySelector(".speak-btn");
      aiSpeakBtn.addEventListener("click", (e) => { e.stopPropagation(); speak(text, aiSpeakBtn); });
      if (opts.correction) {
        const note = document.createElement("div");
        note.className = "correction-note";
        note.textContent = opts.correction;
        d.appendChild(note);
      }
      container.appendChild(d);
      return d;
    }

    async function sendAiMessage(t, els) {
      const text = els.inputEl.value.trim();
      if (!text || els.sendBtn.disabled) return;

      const history = aiHistory[t.id] || (aiHistory[t.id] = []);
      const payloadHistory = history.slice(-16).map(h => ({ role: h.role, text: h.text }));
      const inputLanguage = els.lang || "en";

      els.inputEl.value = "";
      els.inputEl.disabled = true;
      els.sendBtn.disabled = true;
      els.statusEl.textContent = inputLanguage === "id" ? "Menerjemahkan & AI sedang membalas..." : "AI sedang mengetik...";

      try {
        const res = await fetch("/api/percakapan/chat", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({
            partner: t.partner,
            title: t.title,
            blurb: t.blurb,
            message: text,
            input_language: inputLanguage,
            history: payloadHistory,
          }),
        });

        const data = await res.json();
        if (!res.ok) throw new Error(data.error || "Terjadi kesalahan.");

        renderAiLine(els.linesEl, t, "user", data.message_en, {
          translation: data.message_translation,
          correction: data.correction,
        });
        history.push({
          role: "user",
          text: data.message_en,
          translation: data.message_translation,
          correction: data.correction,
        });

        renderAiLine(els.linesEl, t, "assistant", data.reply, { translation: data.translation });
        history.push({ role: "assistant", text: data.reply, translation: data.translation });

        els.statusEl.textContent = "";
      } catch (err) {
        els.statusEl.textContent = err.message || "Gagal menghubungi AI. Coba lagi.";
        els.inputEl.value = text;
      } finally {
        els.inputEl.disabled = false;
        els.sendBtn.disabled = false;
        els.inputEl.focus();
        els.linesEl.parentElement.scrollTop = els.linesEl.parentElement.scrollHeight;
      }
    }

    /* ---------------- dialog view ---------------- */
    function openTopic(t) {
      document.getElementById("dlg-title").textContent = t.title;
      document.getElementById("dlg-day").textContent = `${t.day} · dengan ${t.partner}`;

      const body = document.getElementById("dlg-body");
      body.innerHTML = `
    <div class="toggle-row">
      <span>Tampilkan terjemahan</span>
      <div class="switch" id="sw" role="switch" tabindex="0" aria-checked="${showAllTrans}" aria-label="Tampilkan terjemahan semua baris"></div>
    </div>
    <div id="lines"></div>
    <div class="keyphrases">
      <div class="section-label"><div class="eyebrow">Frasa kunci</div><hr></div>
      <ul>${t.keys.map(k => `<li><b>${k[0]}</b><em>${k[1]}</em></li>`).join("")}</ul>
    </div>
    <div class="ai-practice">
      <div class="section-label"><div class="eyebrow">Latihan bebas dengan AI</div><hr></div>
      <p style="font-size:13px;color:var(--ink-soft);margin-bottom:12px">Lanjutkan percakapan dengan ${escapeHtml(t.partner)} — balasan AI ini sungguhan merespons apa yang kamu tulis.</p>
      <div id="ai-lines"></div>
      <div class="ai-lang-toggle" role="group" aria-label="Bahasa yang kamu ketik">
        <button type="button" class="lang-btn on" data-lang="en">Ketik Inggris</button>
        <button type="button" class="lang-btn" data-lang="id">Ketik Indonesia</button>
      </div>
      <div class="ai-input-row">
        <input type="text" id="ai-input" placeholder="Ketik balasanmu dalam Bahasa Inggris..." autocomplete="off">
        <button class="btn" id="ai-send" type="button">Kirim</button>
      </div>
      <p class="ai-status" id="ai-status" aria-live="polite"></p>
    </div>`;

      const lines = body.querySelector("#lines");
      t.lines.forEach(l => {
        const d = document.createElement("div");
        d.className = "line" + (l.me ? " me" : "") + (showAllTrans ? " show" : "");
        d.innerHTML = `
      <div class="who">${l.me ? "Kamu" : t.partner}</div>
      <div class="bubble"><p>${l.en}${speakBtnHtml("speak-btn-inline")}</p><div class="trans">${l.id}</div></div>`;
        d.querySelector(".bubble").addEventListener("click", () => d.classList.toggle("show"));
        const lineSpeakBtn = d.querySelector(".speak-btn");
        lineSpeakBtn.addEventListener("click", (e) => { e.stopPropagation(); speak(l.en, lineSpeakBtn); });
        lines.appendChild(d);
      });

      const sw = body.querySelector("#sw");
      const flip = () => {
        showAllTrans = !showAllTrans;
        sw.setAttribute("aria-checked", String(showAllTrans));
        lines.querySelectorAll(".line").forEach(l => l.classList.toggle("show", showAllTrans));
      };
      sw.addEventListener("click", flip);
      sw.addEventListener("keydown", e => {
        if (e.key === " " || e.key === "Enter") { e.preventDefault(); flip(); }
      });

      const aiLinesEl = body.querySelector("#ai-lines");
      const aiEls = {
        inputEl: body.querySelector("#ai-input"),
        sendBtn: body.querySelector("#ai-send"),
        linesEl: aiLinesEl,
        statusEl: body.querySelector("#ai-status"),
        lang: "en",
      };
      (aiHistory[t.id] || []).forEach(h =>
        renderAiLine(aiLinesEl, t, h.role, h.text, { translation: h.translation, correction: h.correction })
      );
      aiEls.sendBtn.addEventListener("click", () => sendAiMessage(t, aiEls));
      aiEls.inputEl.addEventListener("keydown", e => {
        if (e.key === "Enter") { e.preventDefault(); sendAiMessage(t, aiEls); }
      });

      body.querySelectorAll(".lang-btn").forEach(b => {
        b.addEventListener("click", () => {
          aiEls.lang = b.dataset.lang;
          body.querySelectorAll(".lang-btn").forEach(x => x.classList.toggle("on", x === b));
          aiEls.inputEl.placeholder = aiEls.lang === "id"
            ? "Ketik dalam Bahasa Indonesia, nanti diterjemahkan otomatis..."
            : "Ketik balasanmu dalam Bahasa Inggris...";
        });
      });

      show("dialog", { tab: "percakapan" });
    }

    /* ---------------- latihan (quiz susun kata / lengkapi / padankan / menyimak) ---------------- */
    const latihanBody = document.getElementById("latihan-body");
    const QUIZ_TYPE_LABELS = {
      arrange: "Susun kata",
      fill_blank: "Lengkapi kalimat",
      match_meaning: "Padankan arti",
      listening: "Menyimak",
    };
    let quizQueue = [];
    let quizIndex = 0;
    let quizScore = 0;
    let currentQuizType = null;

    function shuffle(arr) {
      const a = arr.slice();
      for (let i = a.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [a[i], a[j]] = [a[j], a[i]];
      }
      return a;
    }

    function showLatihanMenu() {
      quizInProgress = false;

      if (EXERCISES.length === 0) {
        latihanBody.innerHTML = `<div class="card"><p style="font-size:14px;color:var(--ink-soft)">Belum ada latihan. Buka dashboard lalu klik "Buat/perbarui latihan" untuk membuatnya otomatis dari kosakata yang sudah ada.</p></div>`;
        return;
      }

      const counts = {};
      EXERCISES.forEach(ex => { counts[ex.type] = (counts[ex.type] || 0) + 1; });

      const buttonsHtml = Object.keys(QUIZ_TYPE_LABELS)
        .filter(type => counts[type] > 0)
        .map(type => `
          <button type="button" class="quiz-type-btn" data-type="${type}">
            <span>${QUIZ_TYPE_LABELS[type]}</span>
            <span class="quiz-type-count">${counts[type]} soal</span>
          </button>`)
        .join("");

      latihanBody.innerHTML = `
      <div class="card">
        <p style="font-size:13px;color:var(--ink-soft);margin-bottom:12px">Pilih jenis latihan yang ingin kamu kerjakan.</p>
        <div class="quiz-type-list">${buttonsHtml}</div>
      </div>`;

      latihanBody.querySelectorAll(".quiz-type-btn").forEach(btn => {
        btn.addEventListener("click", () => {
          if (!confirm("Mulai latihan sekarang?")) return;
          startLatihanByType(btn.dataset.type);
        });
      });
    }

    function startLatihanByType(type) {
      currentQuizType = type;
      quizInProgress = true;
      quizQueue = shuffle(EXERCISES.filter(ex => ex.type === type));
      quizIndex = 0;
      quizScore = 0;
      renderQuizQuestion();
    }

    function quizProgressHtml() {
      return `<div class="quiz-progress"><span>${QUIZ_TYPE_LABELS[currentQuizType]} — ${quizIndex + 1} / ${quizQueue.length}</span><span>Skor: ${quizScore}</span></div>`;
    }

    function renderQuizQuestion() {
      if (quizIndex >= quizQueue.length) {
        quizInProgress = false;
        latihanBody.innerHTML = `
      <div class="card quiz-summary">
        <h3>Latihan selesai!</h3>
        <p style="font-size:14px;color:var(--ink-soft)">${QUIZ_TYPE_LABELS[currentQuizType]} — skor kamu: <b>${quizScore}</b> dari <b>${quizQueue.length}</b>.</p>
        <button class="btn" type="button" id="quiz-restart" style="width:auto;padding:12px 22px;margin-top:10px">Ulangi</button>
        <button type="button" id="quiz-back-to-menu" style="width:auto;padding:12px 22px;margin-top:10px;margin-left:8px;background:none;border:1px solid var(--rule);border-radius:12px;cursor:pointer;font-family:var(--display);font-weight:700">Pilih Jenis Lain</button>
      </div>`;
        document.getElementById("quiz-restart").addEventListener("click", () => startLatihanByType(currentQuizType));
        document.getElementById("quiz-back-to-menu").addEventListener("click", showLatihanMenu);
        return;
      }

      const ex = quizQueue[quizIndex];
      if (ex.type === "arrange") renderArrangeQuestion(ex);
      else if (ex.type === "listening") renderListeningQuestion(ex);
      else renderChoiceQuestion(ex);
    }

    function nextQuizQuestion(correct) {
      if (correct) quizScore++;

      const card = latihanBody.querySelector(".quiz-card");
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "btn";
      btn.id = "quiz-next";
      btn.style.cssText = "width:auto;padding:12px 22px;margin-top:4px";
      btn.textContent = "Lanjut";
      btn.addEventListener("click", () => {
        quizIndex++;
        renderQuizQuestion();
      });
      card.appendChild(btn);
      btn.focus();
    }

    function renderArrangeQuestion(ex) {
      const correctTokens = ex.sentence.trim().split(/\s+/);
      const tokens = shuffle(correctTokens);

      latihanBody.innerHTML = `
      ${quizProgressHtml()}
      <div class="card quiz-card">
        <div class="quiz-instruction">Susun kata menjadi kalimat yang benar ${speakBtnHtml("speak-btn-inline")}</div>
        <div class="quiz-slots" id="quiz-slots"></div>
        <div class="quiz-tokens" id="quiz-tokens"></div>
        <p class="quiz-feedback" id="quiz-feedback"></p>
        <button class="btn" type="button" id="quiz-check" style="width:auto;padding:12px 20px;margin-top:10px" disabled>Cek</button>
      </div>`;

      const slotsEl = document.getElementById("quiz-slots");
      const tokensEl = document.getElementById("quiz-tokens");
      const checkBtn = document.getElementById("quiz-check");
      const feedbackEl = document.getElementById("quiz-feedback");
      const speakBtn = latihanBody.querySelector(".speak-btn");
      speakBtn.addEventListener("click", () => speak(ex.sentence, speakBtn));

      const chosen = [];

      function renderTokens() {
        tokensEl.innerHTML = "";
        tokens.forEach((word, i) => {
          if (chosen.includes(i)) return;
          const b = document.createElement("button");
          b.type = "button";
          b.className = "quiz-token";
          b.textContent = word;
          b.addEventListener("click", () => {
            chosen.push(i);
            renderSlots();
            renderTokens();
            checkBtn.disabled = chosen.length !== correctTokens.length;
          });
          tokensEl.appendChild(b);
        });
      }

      function renderSlots() {
        if (chosen.length === 0) {
          slotsEl.innerHTML = `<span class="quiz-slots-empty">Tap kata di bawah untuk menyusun kalimat…</span>`;
          return;
        }
        slotsEl.innerHTML = "";
        chosen.forEach(i => {
          const b = document.createElement("button");
          b.type = "button";
          b.className = "quiz-token placed";
          b.textContent = tokens[i];
          b.addEventListener("click", () => {
            chosen.splice(chosen.indexOf(i), 1);
            renderSlots();
            renderTokens();
            checkBtn.disabled = chosen.length !== correctTokens.length;
          });
          slotsEl.appendChild(b);
        });
      }

      renderTokens();
      renderSlots();

      checkBtn.addEventListener("click", () => {
        const answer = chosen.map(i => tokens[i]).join(" ");
        const correct = answer.toLowerCase() === ex.sentence.toLowerCase();
        feedbackEl.textContent = correct
          ? "Benar! " + (ex.translation || "")
          : `Kurang tepat. Jawaban: ${ex.sentence}${ex.translation ? " — " + ex.translation : ""}`;
        feedbackEl.className = "quiz-feedback " + (correct ? "correct" : "incorrect");
        checkBtn.disabled = true;
        tokensEl.querySelectorAll("button").forEach(b => b.disabled = true);
        slotsEl.querySelectorAll("button").forEach(b => b.disabled = true);
        nextQuizQuestion(correct);
      });
    }

    function renderChoiceQuestion(ex) {
      const isFillBlank = ex.type === "fill_blank";
      const promptText = isFillBlank ? ex.sentence_with_blank : ex.prompt;
      const speakText = isFillBlank ? ex.answer : ex.prompt;
      const options = shuffle(ex.options);

      latihanBody.innerHTML = `
      ${quizProgressHtml()}
      <div class="card quiz-card">
        <div class="quiz-instruction">${isFillBlank ? "Lengkapi kalimat" : "Pilih arti yang benar"}</div>
        <p class="quiz-prompt">${escapeHtml(promptText)}${speakBtnHtml("speak-btn-inline")}</p>
        <div class="quiz-options" id="quiz-options"></div>
        <p class="quiz-feedback" id="quiz-feedback"></p>
      </div>`;

      const speakBtn = latihanBody.querySelector(".speak-btn");
      speakBtn.addEventListener("click", () => speak(speakText, speakBtn));

      const optionsEl = document.getElementById("quiz-options");
      const feedbackEl = document.getElementById("quiz-feedback");

      options.forEach(opt => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "quiz-option";
        b.textContent = opt;
        b.addEventListener("click", () => {
          const correct = opt === ex.answer;
          optionsEl.querySelectorAll("button").forEach(btn => {
            btn.disabled = true;
            if (btn.textContent === ex.answer) btn.classList.add("correct");
          });
          if (!correct) b.classList.add("incorrect");
          feedbackEl.textContent = correct
            ? "Benar!" + (isFillBlank && ex.translation ? " — " + ex.translation : "")
            : `Kurang tepat. Jawaban: ${ex.answer}`;
          feedbackEl.className = "quiz-feedback " + (correct ? "correct" : "incorrect");
          nextQuizQuestion(correct);
        });
        optionsEl.appendChild(b);
      });
    }

    function renderListeningQuestion(ex) {
      const options = shuffle(ex.options);

      latihanBody.innerHTML = `
      ${quizProgressHtml()}
      <div class="card quiz-card">
        <div class="quiz-instruction">Dengarkan lalu pilih arti yang tepat</div>
        <button type="button" class="quiz-listen-btn" id="quiz-listen" aria-label="Putar audio">🔊 Putar audio</button>
        <div class="quiz-options" id="quiz-options"></div>
        <p class="quiz-feedback" id="quiz-feedback"></p>
      </div>`;

      const listenBtn = document.getElementById("quiz-listen");
      listenBtn.addEventListener("click", () => speak(ex.sentence, listenBtn));

      const optionsEl = document.getElementById("quiz-options");
      const feedbackEl = document.getElementById("quiz-feedback");

      options.forEach(opt => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = "quiz-option";
        b.textContent = opt;
        b.addEventListener("click", () => {
          const correct = opt === ex.answer;
          optionsEl.querySelectorAll("button").forEach(btn => {
            btn.disabled = true;
            if (btn.textContent === ex.answer) btn.classList.add("correct");
          });
          if (!correct) b.classList.add("incorrect");
          feedbackEl.textContent = correct
            ? `Benar! "${ex.sentence}"`
            : `Kurang tepat. Kalimatnya: "${ex.sentence}"`;
          feedbackEl.className = "quiz-feedback " + (correct ? "correct" : "incorrect");
          nextQuizQuestion(correct);
        });
        optionsEl.appendChild(b);
      });
    }

  </script>
</body>

</html>
