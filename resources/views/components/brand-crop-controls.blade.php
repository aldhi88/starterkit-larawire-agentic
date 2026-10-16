<div class="modal-header">
    <h3 class="modal-title" x-bind:id="`${field}-crop-title`" x-text="kind === 'favicon' ? 'Crop Favicon' : 'Crop Logo Aplikasi'"></h3>
</div>
<div class="modal-body starter-brand-crop-body">
    <p class="text-secondary small">Geser gambar atau gunakan pengaturan posisi. Area kotak-kotak tetap transparan pada hasil PNG.</p>
    <canvas x-ref="cropCanvas" class="starter-brand-crop-canvas" tabindex="0" role="img" aria-label="Pratinjau crop; gunakan tombol panah untuk menggeser gambar"
        x-on:pointerdown="startDrag($event)" x-on:pointermove="drag($event)" x-on:pointerup="dragPoint = null" x-on:pointercancel="dragPoint = null"
        x-on:keydown.arrow-left.prevent="move(-10, 0)" x-on:keydown.arrow-right.prevent="move(10, 0)"
        x-on:keydown.arrow-up.prevent="move(0, -10)" x-on:keydown.arrow-down.prevent="move(0, 10)"></canvas>
    <div class="starter-brand-crop-fit">
        <button type="button" class="btn btn-outline-primary" x-on:click="fit(false)" x-bind:disabled="uploading">Tampilkan Utuh</button>
        <button type="button" class="btn btn-outline-primary" x-on:click="fit(true)" x-bind:disabled="uploading">Penuhi Bingkai</button>
    </div>
    <label class="form-label" x-bind:for="`${field}-zoom`">Zoom <span x-text="`${Math.round(zoom * 100)}%`"></span></label>
    <input type="range" min="1" max="4" step="0.01" x-bind:id="`${field}-zoom`" x-model.number="zoom" x-on:input="draw()" x-bind:disabled="uploading">
    <label class="form-label" x-bind:for="`${field}-horizontal`">Posisi horizontal</label>
    <input type="range" min="-100" max="100" step="1" x-bind:id="`${field}-horizontal`" x-model.number="panX" x-on:input="draw()" x-bind:disabled="uploading">
    <label class="form-label" x-bind:for="`${field}-vertical`">Posisi vertikal</label>
    <input type="range" min="-100" max="100" step="1" x-bind:id="`${field}-vertical`" x-model.number="panY" x-on:input="draw()" x-bind:disabled="uploading">
    <div class="text-secondary small" x-text="kind === 'favicon' ? 'Hasil: PNG 256 × 256 px (1:1).' : 'Hasil: PNG 800 × 200 px (4:1).'"></div>
    <div class="text-danger small" x-show="error" x-text="error" role="alert"></div>
    <div class="text-secondary small" x-show="uploading" role="status" x-text="`Mengunggah hasil crop… ${progress}%`"></div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-outline-secondary" x-on:click="closeCrop()" x-bind:disabled="uploading">Batal</button>
    <button type="button" class="btn btn-primary" x-on:click="applyCrop()" x-bind:disabled="uploading || ! image">Gunakan Hasil Crop</button>
</div>
