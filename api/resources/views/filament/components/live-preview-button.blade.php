@php($storefrontUrl = rtrim(config('services.storefront.url'), '/'))

<x-filament::button
    tag='a'
    :href='$storefrontUrl'
    target='_blank'
    rel='noopener noreferrer'
    icon='heroicon-o-arrow-top-right-on-square'
    color='gray'
    size='sm'
    labeled-from='sm'
>
    Live Preview
</x-filament::button>
