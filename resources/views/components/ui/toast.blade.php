{{-- Toast region; include once per layout. Show a toast from JS with window.toast({ category, title, description }).
     The templates hold the status icons, so the JS never builds SVG markup. --}}
<div id="toaster" class="toaster"></div>
@foreach (['check-circle', 'x-circle', 'warning', 'info'] as $toastIcon)
    <template data-icon="{{ $toastIcon }}"><x-ui.icon :name="$toastIcon" /></template>
@endforeach
