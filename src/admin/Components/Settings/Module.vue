<template>
	<div>
		<h1>This is Module page</h1>
	</div>
</template>

<script>
export default {
	name: "module",
	data() {
		return {
			moduleName: ""
		}
	},
	created() {
		this.moduleName = this.$route.fullPath.substring(10)
	}
}
</script>