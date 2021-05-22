<template>
    <div>
        <h1>
            Componente de ejemplo
        </h1>
    </div>
</template>

<script lang="ts">
    import { onMounted, ref, watch, } from 'vue';

    export default {
        name: 'GenericPageListElements',
        setup() {
            onMounted(() => console.log('Component mounted.'));
        }

    }
</script>
