<div class="gen-topic-card" data-id="{{ $topic->id }}">
  <div class="head" data-toggle>
    <div>
      <b>Kasus {{ $caseNumber }} · {{ $topic->title }}</b>
      <span>{{ $topic->blurb }} — lawan bicara: {{ $topic->partner }}</span>
    </div>
    <button class="del" type="button" aria-label="Hapus kasus">×</button>
  </div>
  <div class="body">
    @foreach ($topic->lines ?? [] as $line)
      <div class="line">
        <b>{{ ($line['me'] ?? false) ? 'Kamu' : $topic->partner }}</b>
        {{ $line['en'] ?? '' }} <span style="color:var(--ink-soft)">— {{ $line['id'] ?? '' }}</span>
      </div>
    @endforeach
    <div class="keys">
      @foreach ($topic->keys ?? [] as $key)
        <div>• <b>{{ $key['en'] ?? '' }}</b> — {{ $key['id'] ?? '' }}</div>
      @endforeach
    </div>
  </div>
</div>
