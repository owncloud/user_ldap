<template>
    <div v-if="!active">
        loading
    </div>
</template>
<script>
export default {
    props : ['active']
}
</script>
<style lang="scss" scoped>
    div {
		position: absolute;
		z-index: 99;
		top: 0;
		right: 0;
		bottom: 0;
        left: 0;
        text-indent: -9999px;

		background: {
			color: rgba(white, .666);
			position: center center;
			repeat: no-repeat;
			image: url("../../core/img/loading.gif");
		} 
    }
</style>
