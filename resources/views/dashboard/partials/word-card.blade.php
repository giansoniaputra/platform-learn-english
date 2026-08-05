<div class="gen-word-card" data-id="{{ $word->id }}">
  <button class="del" type="button" aria-label="Hapus kata">×</button>
  <div class="head">
    <span class="en">{{ $word->en }}</span>
    <span class="ipa">{{ $word->ipa }}</span>
    <span class="pos">{{ $word->pos }}</span>
  </div>
  <div class="id">{{ $word->translation }}</div>
  @if ($word->example)
    <p class="ex">{{ $word->example }}</p>
    @if ($word->example_translation)
      <p class="ex-id">{{ $word->example_translation }}</p>
    @endif
  @endif
  @if ($word->verb1 || $word->verb2 || $word->verb3)
    <div class="verbs">V1 {{ $word->verb1 ?? '-' }} · V2 {{ $word->verb2 ?? '-' }} · V3 {{ $word->verb3 ?? '-' }}</div>
  @endif
</div>
