<template x-teleport="body">
    <div class="modal starter-brand-crop-modal dashcode-crop-modal" x-cloak x-show="cropOpen" role="dialog" aria-modal="true" x-bind:aria-labelledby="`${field}-crop-title`"
        x-on:click.self="closeCrop()" x-on:keydown.escape.prevent.stop="closeCrop()" x-on:keydown.tab="trapFocus($event)">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                @include('starter-shared::components.brand-crop-controls')
            </div>
        </div>
    </div>
</template>
