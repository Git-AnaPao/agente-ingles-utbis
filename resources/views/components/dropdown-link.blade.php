{{--
    Enlace de dropdown — hover con Verde muy tenue
--}}
<a {{ $attributes->merge([
    'class' => 'block w-full px-4 py-2.5 text-start text-sm leading-5 transition duration-150 ease-in-out rounded-lg focus:outline-none',
    'style' => 'color:#27594B; font-family:Inter,sans-serif;',
    'onmouseover' => "this.style.backgroundColor='#F2F2F2'",
    'onmouseout'  => "this.style.backgroundColor='transparent'",
]) }}>
    {{ $slot }}
</a>
