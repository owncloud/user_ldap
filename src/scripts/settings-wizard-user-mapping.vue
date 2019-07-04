<template>
    <select :ref="reference" class="grow">
        <option v-for="(item, key) in map" :key="key">
            {{ item.join(', ') }} ({{ key }})
        </option>
    </select>
</template>
<script>


export default {
    props : ['reference', 'map'],
}
</script>
