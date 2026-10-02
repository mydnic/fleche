{{-- Rybbit (our own instance, cookieless), fleche.io only: self-hosted copies never load it. --}}
@if (config('fleche.edition') === 'cloud')
    <script src="https://rybbit.dricle.be/api/script.js" data-site-id="38" defer></script>
@endif
