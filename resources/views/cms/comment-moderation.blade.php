@php
    $dash = \Aldhi88\StarterKit\Support\Starter\StarterTheme::key() === 'dashcode';
    $cardBody = $dash ? 'card-body p-5' : 'card-body';
    $statuses = $cmsResource->statusOptions();
@endphp
<div class="cms-authoring-layout">
    <div class="cms-authoring-main">
        <section class="card" data-starter-region="content-form">
            <div class="card-header"><h3 class="card-title">{{ $form['parent_id'] ? 'Balasan editor' : 'Komentar pembaca' }}</h3></div>
            <div class="{{ $cardBody }} cms-authoring-stack">
                <div><strong>{{ $form['author_name'] }}</strong>
                    <small class="form-hint">{{ $form['content_title'] }}</small>
                </div>
                <p class="cms-comment-text">{{ $form['body'] }}</p>
                @if ($form['parent_id'] && $form['parent_available'])
                    <a class="cms-inline-link" href="{{ route('cms.comments.edit', ['contentId' => $form['parent_id']]) }}" data-starter-navigate>Lihat komentar utama</a>
                @endif
            </div>
        </section>
        @unless ($form['parent_id'])
            <section class="card">
                <div class="card-header"><h3 class="card-title">Balasan</h3></div>
                <div class="{{ $cardBody }} cms-authoring-stack">
                    @if ($form['replies'])
                        <ul class="cms-comment-replies" aria-label="Balasan terbaru">
                            @foreach ($form['replies'] as $reply)
                                <li><strong>{{ $reply['author_name'] }}</strong> <small class="form-hint">{{ $statuses[$reply['status']] ?? '' }}</small>
                                    <p class="cms-comment-text">{{ $reply['body'] }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div>
                        <label for="cms-reply-body" class="form-label">Balas sebagai {{ $this->moderatorName }}</label>
                        <textarea id="cms-reply-body" rows="4" maxlength="10000" class="form-control" wire:model="form.reply_body" placeholder="Tulis balasan untuk pembaca…"></textarea>
                        <p class="form-hint">Balasan tampil di website selama komentar utama terbit. Kosongkan untuk menyimpan status saja.</p>
                        @error('form.reply_body')<div class="text-danger" role="alert">{{ $message }}</div>@enderror
                    </div>
                </div>
            </section>
        @endunless
    </div>
    <aside class="cms-authoring-sidebar">
        <section class="card" data-starter-region="publication-form">
            <div class="card-header"><h3 class="card-title">Moderasi</h3></div>
            <div class="{{ $cardBody }}">
                @include('starter.cms.fields.input', ['field' => 'status', 'label' => 'Status komentar', 'type' => 'select', 'choices' => $statusChoices, 'noEmpty' => true, 'required' => true])
                <p class="form-hint">Pilih Terbit untuk menampilkan komentar, Disembunyikan untuk menariknya dari website, atau Diblokir untuk menolak komentar ini.</p>
            </div>
        </section>
    </aside>
</div>
