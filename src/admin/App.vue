<template>
    <div class="wrap">
        <div class="payform_admin">
            <router-view></router-view>
        </div>
    </div>
</template>
<script type="text/babel">
    import Navigation from './Components/Global/Navigation';

    export default {
        name: 'WPPayFormApp',
        components: {Navigation},
    }
</script>