<template>
    <div>
        test-mixin
    </div>
</template>

<style scoped>
* {
    border: 3px solid red;
}
</style>

<script>
export default {
    name: "mixin-test",
    methods: {
        run() {
            return "run";
        }
    }
}
</script>