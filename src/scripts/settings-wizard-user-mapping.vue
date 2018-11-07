<template>
    <select :ref="reference" class="grow">
        <option value="baseDN">{{ map.baseDN }} (baseDN)</option>
        <option value="displayNameAttribute">{{ map.displayNameAttribute }} (displayNameAttribute)</option>
    </select>
</template>
<script>
export default {
    props : ['reference', 'map']
}
</script>
