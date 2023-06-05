<template>

    <div>
        <!-- Modal -->
        <div class="v-box-image-cropper-modal" v-if="isActive">
            <cropper
                :src="img"
                :default-size="{width: 120, height: 120}"
            />

            <div>
                <span class="v-image-cropper-button-open" @click="toggleModalActive">
                BTN TEST CERRAR MODAL
            </span>
            </div>
        </div>


        <!-- Imagen de previsualización tras cerrar modal -->
        <div>

            <img :src="img" alt="Imagen por defecto">

        </div>

        <!-- Botón para abrir modal -->
        <div>
            <span class="v-image-cropper-button-open" @click="toggleModalActive">
                BTN TEST CAMBIAR IMAGEN
            </span>

        </div>
    </div>




</template>

<script>
    import { Cropper } from 'vue-advanced-cropper';
    import 'vue-advanced-cropper/dist/style.css';
    import {ref} from "vue";

    export default {
        name: 'ImageCropper',
        components: {
            Cropper,
        },
        setup() {

            const isActive = ref(false);

            const toggleModalActive = () => isActive.value = !isActive.value;

            return {
                img: 'http://localhost:8000/images/logo.png',
                isActive,

                toggleModalActive,
            };
        },

        // https://advanced-cropper.github.io/vue-advanced-cropper/guides/advanced-recipes.html#default-size-and-position
        /*
        methods: {
            change({ coordinates, canvas }) {
                console.log(coordinates, canvas);
            },
            defaultSize({ imageSize, visibleArea }) {
                return {
                    width: (visibleArea || imageSize).width,
                    height: (visibleArea || imageSize).height,
                };
            }
        },

         */
    };
</script>

<style lang="css" scoped>
    .v-box-image-cropper-modal {
        position: fixed;
        top: 0;
        left: 0;
        margin: 0;
        padding: 0;
        width: 100vw;
        height: 100vh;
        background-color: rgba(32, 32, 32, 0.7);
        z-index: 9999;
    }

    .v-image-cropper-button-open {
        position: relative;

        margin: 30px auto;
        padding: 3px;

        overflow: hidden;

        border-width: 0;
        outline: none;
        border-radius: 2px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .6);

        background-color: #2ecc71;
        color: #ecf0f1;

        box-sizing: content-box;

        transition: background-color .3s;
    }

    .v-image-cropper-button-open {
        cursor: pointer;
    }

    .v-image-cropper-button-open:hover, .v-image-cropper-button-open:focus {
        background-color: #27ae60;
    }

    .v-image-cropper-button-open span {
        display: block;
        padding: 12px 24px;
    }

</style>
