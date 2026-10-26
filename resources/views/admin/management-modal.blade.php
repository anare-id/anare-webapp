<dialog class="management-dialog" aria-labelledby="management-dialog-title" data-management-dialog data-return-url="{{ $returnUrl }}">
    <header class="dialog-header"><div><span class="page-eyebrow">MANAGEMENT USER</span><h2 id="management-dialog-title">{{ $title }}</h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Tutup modal" title="Tutup"><svg class="icon" aria-hidden="true"><use href="#a-close"/></svg></button></header>
    <div class="management-dialog-body">
        @if($errors->any())<div class="form-error" role="alert" tabindex="-1"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        {{ $slot }}
    </div>
</dialog>
