<template>

    <div>
        <!-- Modal -->
        <div class="v-box-image-cropper-modal" v-if="isActive">
            <div class="v-container-cropper-modal">
                <cropper
                    ref="cropper"
                    :src="img"
                    :default-size="defaultSize"
                    :auto-zoom="true"
                    :stencil-props="{
                        aspectRatio: aspectRatiosRestriction[0] / aspectRatiosRestriction[1],
                    }"
                />

                <div class="v-box-tools">
                    <div class="v-mt-1">
                        <span @click="() => zoom(2)"
                              class="tools-button">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path fill="#FFF"
                                                                                             d="M14.8 16c-.3 0-.6-.1-.9-.4l-3.3-3.3c-1.1.8-2.5 1.2-3.8 1.2-3.8 0-6.8-3-6.8-6.7C0 3 3 0 6.8 0c3.7 0 6.8 3 6.8 6.8 0 1.4-.4 2.7-1.2 3.8l3.3 3.3c.2.2.4.5.4.9-.1.7-.6 1.2-1.3 1.2zm-8-13.5c-2.4 0-4.3 1.9-4.3 4.3s1.9 4.3 4.3 4.3 4.3-1.9 4.3-4.3-2-4.3-4.3-4.3z"/><path
                            fill="#FFF"
                            d="M9 5.8H7.8V4.5c0-.2-.1-.3-.3-.3H6.1c-.2 0-.3.1-.3.3v1.3H4.5c-.2 0-.3.1-.3.3v1.4c0 .2.1.3.3.3h1.3V9c0 .2.1.3.3.3h1.4c.2 0 .3-.1.3-.3V7.8H9c.2 0 .3-.1.3-.3V6.1c0-.2-.1-.3-.3-.3z"/></svg>
                        </span>

                            <span @click="() => zoom(0.5 )"
                                  class="tools-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"><path fill="#FFF"
                                                                                                 d="M14.8 16c-.3 0-.6-.1-.9-.4l-3.3-3.3c-1.1.8-2.5 1.2-3.8 1.2-3.8 0-6.8-3-6.8-6.7C0 3 3 0 6.8 0c3.7 0 6.8 3 6.8 6.8 0 1.4-.4 2.7-1.2 3.8l3.3 3.3c.2.2.4.5.4.9-.1.7-.6 1.2-1.3 1.2zm-8-13.5c-2.4 0-4.3 1.9-4.3 4.3s1.9 4.3 4.3 4.3 4.3-1.9 4.3-4.3-2-4.3-4.3-4.3z"/><path
                                fill="#FFF"
                                d="M9.3 7.5c0 .2-.1.3-.3.3H4.5c-.2 0-.3-.1-.3-.3V6.1c0-.2.1-.3.3-.3H9c.2 0 .3.1.3.3v1.4z"/></svg>
                        </span>
                    </div>

                    <div class="v-mt-1">
                        <span @click="() => flip(360, 0 )"
                          class="tools-button">
                            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                 width="16px" height="16px" viewBox="0 0 16 16" enable-background="new 0 0 16 16"
                                 xml:space="preserve">
    <line fill="#FFFFFF" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round" stroke-miterlimit="10" x1="8" y1="1"
          x2="8" y2="14.9"/>
    <g>
        <g>

                <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                      x1="11.5" y1="12.8" x2="12" y2="12.8"/>
            <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                      points="14.2,12.8
                14.7,12.8 14.7,12.3 		"/>

                <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                      stroke-dasharray="0.8091,2.4273" x1="14.7" y1="9.9" x2="14.7" y2="4.6"/>
            <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                      points="14.7,3.4
                14.7,2.9 14.2,2.9 		"/>

                <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" x1="12"
                      y1="2.9" x2="11.5" y2="2.9"/>
        </g>
    </g>
    <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round" stroke-miterlimit="10" points="4.5,2.9
        1.3,2.9 1.3,12.8 4.5,12.8 "/>
    </svg>
                        </span>

                        <span @click="() => flip(0, 360 )"
                              class="tools-button">
                            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                 width="16px" height="16px" viewBox="0 0 16 16" enable-background="new 0 0 16 16"
                                 xml:space="preserve">
                            <line fill="#FFFFFF" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"
                                  stroke-miterlimit="10" x1="15" y1="8"
                                  x2="1" y2="8"/>
                            <g>
                                <g>
                                        <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                              stroke-linejoin="round"
                                              x1="3.2" y1="11.5" x2="3.2" y2="12"/>
                                    <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                              stroke-linejoin="round"
                                              points="3.2,14.2
                                        3.2,14.7 3.7,14.7 		"/>

                                        <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                              stroke-linejoin="round"
                                              stroke-dasharray="0.8091,2.4273" x1="6.1" y1="14.7" x2="11.4" y2="14.7"/>
                                    <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                              stroke-linejoin="round"
                                              points="12.6,14.7
                                        13.1,14.7 13.1,14.2 		"/>

                                        <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                              stroke-linejoin="round"
                                              x1="13.1" y1="12" x2="13.1" y2="11.5"/>
                                </g>
                            </g>
                            <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round"
                                      stroke-miterlimit="10" points="13.1,4.4
                                13.1,1.3 3.2,1.3 3.2,4.4 "/>
                            </svg>
                        </span>

                        <span @click="() => rotate(90 )"
                              class="tools-button">
                            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                 width="16px" height="16px" viewBox="0 0 16 16" enable-background="new 0 0 16 16"
                                 xml:space="preserve">
                            <g enable-background="new    ">
                                <path fill="#FFFFFF" d="M1.5,8c0-3.7,3-6.5,6.5-6.5c1.7,0,3.3,0.7,4.5,1.8l1.1-1.1c0.1-0.1,0.4-0.1,0.6-0.1
                                    c0.1,0.1,0.3,0.3,0.3,0.6v3.8c0,0.3-0.3,0.6-0.6,0.6h-3.8C10,6.9,9.8,6.7,9.7,6.6C9.6,6.4,9.7,6.2,9.8,6L11,4.9
                                    C10.1,4,9.1,3.6,8,3.6c-2.4,0-4.4,2-4.4,4.4s2,4.4,4.4,4.4c1.4,0,2.5-0.6,3.4-1.7l0.1-0.1c0.1,0,0.1,0,0.3,0.1l1.1,1.1
                                    c0.1,0.1,0.1,0.3,0,0.4c-1.1,1.4-3,2.3-4.9,2.3C4.3,14.5,1.5,11.7,1.5,8z"/>
                            </g>
                            </svg>
                        </span>

                        <span @click="() => rotate(-90 )"
                              class="tools-button">
                            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                 width="16px" height="16px" viewBox="0 0 16 16" enable-background="new 0 0 16 16"
                                 xml:space="preserve">
                            <g enable-background="new    ">
                                <path fill="#FFFFFF" d="M8,14.5c-2,0-3.8-0.8-5.1-2.4c-0.1-0.1-0.1-0.3,0-0.4l1.1-1.1c0,0,0.1-0.1,0.3-0.1c0.1,0,0.1,0,0.1,0.1
                                    c1,1.3,2.1,1.8,3.5,1.8c2.4,0,4.4-2,4.4-4.4s-2-4.4-4.4-4.4c-1.1,0-2.1,0.4-3,1.1l1.1,1.1c0.1,0.3,0.3,0.4,0.1,0.7
                                    C6.2,6.7,6.1,6.9,5.9,6.9H2c-0.3,0-0.6-0.3-0.6-0.6V2.5c0-0.3,0.1-0.4,0.3-0.6c0.1-0.1,0.4,0,0.6,0.1l1.1,1.1
                                    c1.3-1.1,2.8-1.8,4.5-1.8c3.7,0,6.5,3,6.5,6.5S11.7,14.5,8,14.5z"/>
                            </g>
                            </svg>
                        </span>
                    </div>

                    <div class="v-mt-1">
                        <span @click="reset"
                                  class="tools-button">
                            RESET
                        </span>
                    </div>

                </div>

                <div class="v-box-action-buttons">
                    <span class="v-image-cropper-button-open" @click="toggleModalActive">
                        BTN TEST CERRAR MODAL
                    </span>
                </div>
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
import {onMounted, ref} from "vue";
import {Cropper} from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import 'vue-advanced-cropper/dist/theme.compact.css';

export default {
    name: 'ImageCropper',
    components: {
        Cropper,
    },
    props: {
        aspectRatiosRestriction: {
            default: [1, 1]
        }
    },
    setup(props) {
        console.log(props)

        const isActive = ref(false);
        //const cropper = ref(null);

        const toggleModalActive = () => isActive.value = !isActive.value;

        //const zoom = () => cropper.value?.zoom(2);

        onMounted(() => {
            //console.log('test 1')
            //console.log(cropper.value);
        });

        return {
            img: 'http://localhost:8000/images/logo.png',
            isActive,

            toggleModalActive,
            //zoom,
        };
    },

    // https://advanced-cropper.github.io/vue-advanced-cropper/guides/advanced-recipes.html#default-size-and-position
    methods: {

        reset() {
            this.zoom(0);
            this.flip();
            this.rotate();

        },
        zoom(quantity = 2) {
            this.$refs.cropper.zoom(quantity);
        },

        move() {
            this.$refs.cropper.move(100, 100)
        },

        defaultSize({imageSize, visibleArea}) {
            return {
                width: (visibleArea || imageSize).width,
                height: (visibleArea || imageSize).height,
            };
        },

        flip(x, y) {
            this.$refs.cropper.flip(x, y);
        },

        rotate(angle) {
            this.$refs.cropper.rotate(angle);
        },

        /*
        change({ coordinates, canvas }) {
            console.log(coordinates, canvas);
        },
        */
    },

};
</script>

<style lang="css" scoped>
.v-mt-1 {
    margin-top: 1rem;
}

.v-box-image-cropper-modal {
    position: fixed;

    display: grid;
    align-items: center;

    top: 0;
    left: 0;
    margin: 0;
    padding: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(32, 32, 32, 0.7);
    z-index: 9999;
}

.v-container-cropper-modal {
    width: 80%;
    height: 80%;
    margin: auto;
    background-color: #f9fafb;
}

@media (max-width: 660px) {
    .v-container-cropper-modal {
        width: 90%;
        height: 90%;
    }
}

@media (max-width: 460px) {
    .v-container-cropper-modal {
        width: 100%;
        height: 100%;
    }
}

.v-box-tools {
    padding: 8px;
    text-align: center;
    width: 100%;
}

.v-box-tools .tools-button {
    margin: 5px;
    padding: 3px;
    width: 120px;
    cursor: pointer;
    box-sizing: border-box;

    background-color: rgba(40, 40, 40, 0.6);
    color: #ecf0f1;
    border-radius: 0.2rem;
}


.v-box-action-buttons {
    text-align: center;
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
