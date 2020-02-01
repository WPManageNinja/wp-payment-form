<template>
    <div class="wppaymform_global">
        <navigation />
        <router-view></router-view>
    </div>
</template>

<script type="text/babel">
    import Navigation from './Navigation';
    export default {
        name: 'global_wrapper',
        components: {Navigation}
    }
</script>