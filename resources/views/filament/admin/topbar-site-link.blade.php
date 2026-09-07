{{-- Oogje in de topbalk (render hook USER_MENU_BEFORE): link naar de publieke site. --}}
<x-filament::icon-button
    tag="a"
    :href="url('/')"
    icon="heroicon-o-eye"
    color="gray"
    size="lg"
    label="Bekijk website"
    tooltip="Bekijk website"
/>
