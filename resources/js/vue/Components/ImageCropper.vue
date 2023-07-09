<template>

    <div>
        <!-- Modal -->
        <div class="v-box-image-cropper-modal" v-if="isActive">
            <div class="v-container-cropper-modal">
                <cropper
                    class="v-cropper"
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

                        <span @click="() => flip(360, 0 )"
                              class="tools-button">
                            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg"
                                 xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
                                 width="16px" height="16px" viewBox="0 0 16 16" enable-background="new 0 0 16 16"
                                 xml:space="preserve">
                                <line fill="#FFFFFF" stroke="#FFFFFF" stroke-width="2" stroke-linecap="round"
                                      stroke-miterlimit="10" x1="8" y1="1"
                                      x2="8" y2="14.9"/>
                                <g>
                                    <g>
                                        <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                              stroke-linejoin="round"
                                              x1="11.5" y1="12.8" x2="12" y2="12.8"/>
                                        <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  points="14.2,12.8
                                            14.7,12.8 14.7,12.3"/>

                                            <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  stroke-dasharray="0.8091,2.4273" x1="14.7" y1="9.9" x2="14.7"
                                                  y2="4.6"/>
                                        <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                                  stroke-linejoin="round"
                                                  points="14.7,3.4
                                            14.7,2.9 14.2,2.9"/>

                                        <line fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linecap="round"
                                              stroke-linejoin="round"
                                              x1="12"
                                              y1="2.9" x2="11.5" y2="2.9"/>
                                    </g>
                                </g>
                                <polyline fill="none" stroke="#FFFFFF" stroke-width="1.5" stroke-linejoin="round"
                                          stroke-miterlimit="10" points="4.5,2.9
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

                <!-- Grid con selector de imagen: URL/HDD -->
                <div class="v-box-image-selector">

                    <div class="v-text-center">
                        Para cambiar imagen, selecciona el origen
                    </div>

                    <div class="v-box-image-selector-origin">
                        <div @click="changeOriginImageSelector('hdd')"
                             class="v-box-image-selector-origin-option"
                             :class="selectorHddActive ? 'v-active' : ''"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                <path
                                    d="M0 96C0 60.7 28.7 32 64 32H448c35.3 0 64 28.7 64 64V280.4c-17-15.2-39.4-24.4-64-24.4H64c-24.6 0-47 9.2-64 24.4V96zM64 288H448c35.3 0 64 28.7 64 64v64c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V352c0-35.3 28.7-64 64-64zM320 416a32 32 0 1 0 0-64 32 32 0 1 0 0 64zm128-32a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/>
                            </svg>
                            HDD
                        </div>

                        <div @click="changeOriginImageSelector('url')"
                             class="v-box-image-selector-origin-option"
                             :class="selectorUrlActive ? 'v-active' : ''"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 640 512">
                                <path
                                    d="M579.8 267.7c56.5-56.5 56.5-148 0-204.5c-50-50-128.8-56.5-186.3-15.4l-1.6 1.1c-14.4 10.3-17.7 30.3-7.4 44.6s30.3 17.7 44.6 7.4l1.6-1.1c32.1-22.9 76-19.3 103.8 8.6c31.5 31.5 31.5 82.5 0 114L422.3 334.8c-31.5 31.5-82.5 31.5-114 0c-27.9-27.9-31.5-71.8-8.6-103.8l1.1-1.6c10.3-14.4 6.9-34.4-7.4-44.6s-34.4-6.9-44.6 7.4l-1.1 1.6C206.5 251.2 213 330 263 380c56.5 56.5 148 56.5 204.5 0L579.8 267.7zM60.2 244.3c-56.5 56.5-56.5 148 0 204.5c50 50 128.8 56.5 186.3 15.4l1.6-1.1c14.4-10.3 17.7-30.3 7.4-44.6s-30.3-17.7-44.6-7.4l-1.6 1.1c-32.1 22.9-76 19.3-103.8-8.6C74 372 74 321 105.5 289.5L217.7 177.2c31.5-31.5 82.5-31.5 114 0c27.9 27.9 31.5 71.8 8.6 103.9l-1.1 1.6c-10.3 14.4-6.9 34.4 7.4 44.6s34.4 6.9 44.6-7.4l1.1-1.6C433.5 260.8 427 182 377 132c-56.5-56.5-148-56.5-204.5 0L60.2 244.3z"/>
                            </svg>
                            URL
                        </div>
                    </div>


                    <!-- Selector de imagen -->
                    <div v-if="selectorHddActive" class="v-text-center">
                        <input type="file" ref="file" @change="loadImage($event)" accept="image/*" />
                    </div>


                    <!-- Input para URL -->
                    <div v-if="selectorUrlActive" class="v-text-center">
                        Link:

                        <input type="text"
                               v-model="urlImage"
                               class="v-image-selector-origin-option-input-url"/>

                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="v-icon-button"
                             @click="loadImageFromUrl"
                             height="1em" viewBox="0 0 576 512">
                            <path
                                d="M272 416c17.7 0 32-14.3 32-32s-14.3-32-32-32H160c-17.7 0-32-14.3-32-32V192h32c12.9 0 24.6-7.8 29.6-19.8s2.2-25.7-6.9-34.9l-64-64c-12.5-12.5-32.8-12.5-45.3 0l-64 64c-9.2 9.2-11.9 22.9-6.9 34.9s16.6 19.8 29.6 19.8l32 0 0 128c0 53 43 96 96 96H272zM304 96c-17.7 0-32 14.3-32 32s14.3 32 32 32l112 0c17.7 0 32 14.3 32 32l0 128H416c-12.9 0-24.6 7.8-29.6 19.8s-2.2 25.7 6.9 34.9l64 64c12.5 12.5 32.8 12.5 45.3 0l64-64c9.2-9.2 11.9-22.9 6.9-34.9s-16.6-19.8-29.6-19.8l-32 0V192c0-53-43-96-96-96L304 96z"/>
                        </svg>
                    </div>

                </div>


                <div class="v-box-action-buttons">
                    <span class="v-image-cropper-buttons v-image-cropper-button-save"
                          @click="uploadImage">
                        Guardar
                    </span>

                    <span class="v-ml-1 v-image-cropper-buttons v-image-cropper-button-close"
                          @click="toggleModalActive">
                        Cancelar
                    </span>
                </div>
            </div>
        </div>


        <!-- Imagen de previsualización tras cerrar modal -->
        <div>

            <img :src="img"
                 @click="toggleModalActive"
                 class="v-image-cropper-preview-image"
                 alt="Imagen por defecto">

        </div>

        <!-- Botón para abrir modal -->
        <div class="v-text-center v-mt-1">
            <span class="v-image-cropper-buttons v-image-cropper-button-open"
                  @click="toggleModalActive">
                Editar imagen
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
        },
        name: {
            default: 'image'
        },
        url: {
            required: true,
        },
        defaultImage: {
            required: true,
        },
        csrf: {
            default: null,
            required: false,
        },
    },
    setup(props) {
        console.log(props)

        const isActive = ref(false);
        //const cropper = ref(null);

        const selectorHddActive = ref(true);
        const selectorUrlActive = ref(false);

        const img = ref(props.defaultImage);

        const urlImage = ref('');

        const toggleModalActive = () => isActive.value = !isActive.value;

        //const zoom = () => cropper.value?.zoom(2);

        onMounted(() => {
            //console.log('test 1')
            //console.log(cropper.value);
        });

        return {
            img,
            isActive,

            toggleModalActive,
            //zoom,
            selectorHddActive,
            selectorUrlActive,
            urlImage,
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


        changeOriginImageSelector(section = 'hdd') {
            if (section === 'hdd') {
                this.selectorHddActive = true;
                this.selectorUrlActive = false;
            } else {
                this.selectorHddActive = false;
                this.selectorUrlActive = true;
            }
        },

        loadImageFromUrl() {
            this.img = this.urlImage;
        },

        /**
         * Load image from HDD
         *
         * @param event
         */
        loadImage(event) {
            // Reference to the DOM input element
            const { files } = event.target;
            // Ensure that you have a file before attempting to read it
            if (files && files[0]) {
                // 1. Revoke the object URL, to allow the garbage collector to destroy the uploaded before file
                if (this.img.src) {
                    URL.revokeObjectURL(this.img.src)
                }
                // 2. Create the blob link to the file to optimize performance:
                const blob = URL.createObjectURL(files[0]);

                // 3. The steps below are designated to determine a file mime type to use it during the
                // getting of a cropped image from the canvas. You can replace it them by the following string,
                // but the type will be derived from the extension and it can lead to an incorrect result:
                //
                // this.image = {
                //    src: blob;
                //    type: files[0].type
                // }

                // Create a new FileReader to read this image binary data
                const reader = new FileReader();
                // Define a callback function to run, when FileReader finishes its job
                reader.onload = (e) => {
                    // Note: arrow function used here, so that "this.image" refers to the image of Vue component
                    /*
                    this.img = {
                        // Set the image source (it will look like blob:http://example.com/2c5270a5-18b5-406e-a4fb-07427f5e7b94)
                        src: blob,
                        // Determine the image type to preserve it during the extracting the image from canvas:
                        //type: getMimeType(e.target.result, files[0].type),
                    };
                     */
                    this.img = blob;
                };
                // Start the reader job - read file as a data url (base64 format)
                reader.readAsArrayBuffer(files[0]);
            }
        },

        uploadImage() {
            const { canvas } = this.$refs.cropper.getResult();

            if (canvas) {

                // Cierro el modal antes de procesar la subida
                this.isActive = false;

                const form = new FormData();


                // Tener en cuenta
                // Añadir token csrf
                // Plantear función fetch global con token csrf
                // Comprobar enctype del form, multipart/form-data
                // Intentar subir en png para mantener canal alfa, en backend se puede convertir a jpg o webp



                const headers = {
                    'Accept': 'application/json',
                    //'Content-Type': 'multipart/form-data',
                };

                if (this.csrf) {
                    headers['X-CSRF-TOKEN'] = this.csrf;
                }

                canvas.toBlob(blob => {
                    form.append('image', blob);

                    this.src = URL.createObjectURL(blob);

                    console.log(this.src);

                    fetch(this.url, {
                        method: 'POST',
                        headers: headers,
                        body: form,
                    })
                        .then(res => res.json())
                        .then(data => {

                            if (data.url) {
                                this.img = data.url;
                            }

                    }).catch(err => {
                        console.log(err);
                    });
                }, 'image/png');
            }
        },
    },

};
</script>

<style lang="css" scoped>
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

.v-cropper {
    width: 100%;
    height: 100%;
    max-height: 300px;
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

.v-image-cropper-buttons {
    cursor: pointer;
    position: relative;
    margin: 30px auto;
    padding: 0.2rem 0.6rem;
    overflow: hidden;
    border-width: 0;
    outline: none;
    border-radius: 2px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, .6);
    box-sizing: content-box;
    transition: background-color .3s;
}

.v-image-cropper-button-close {
    background-color: #e74c3c;
    color: #ecf0f1;
}

.v-image-cropper-button-save {
    background-color: #3498db;
    color: #ecf0f1;
}

.v-image-cropper-button-open {
    background-color: #2ecc71;
    color: #ecf0f1;
}

.v-image-cropper-buttons:hover, .v-image-cropper-buttons:focus {
    background-color: #27ae60;
}

.v-image-cropper-button-open span {
    display: block;
    padding: 12px 24px;
}

/* Imagen de previsualización tras cerrar modal */
.v-image-cropper-preview-image {
    width: 100%;
    height: auto;
    max-width: 200px;
    display: block;
    margin: auto;
    cursor: pointer;
}


/* Estilos para el selector de imágenes, cambiar imagen desde hdd o url */
.v-box-image-selector {
    margin-top: 1rem;
    padding: 1rem;
}

.v-box-image-selector-origin {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-gap: 0.5rem;
    margin: 1rem 0;
    align-items: center;
}

.v-box-image-selector-origin-option {
    text-align: center;
    color: #0056b3;
    fill: #0056b3;
    cursor: pointer;
    border-radius: 0.2rem;
}

.v-box-image-selector-origin-option.v-active {
    background-color: #0056b3;
    color: rgba(255, 255, 255, 0.8);
    fill: rgba(255, 255, 255, 0.8);
    cursor: not-allowed;
}

.v-image-selector-origin-option-input-url {
    width: 100%;
    max-width: 400px;
}

/* Estilos generales de utilidad/maquetación */

.v-text-center {
    text-align: center;
}

.v-mt-1 {
    margin-top: 1rem;
}

.v-ml-1 {
    margin-left: 1rem;
}

.v-icon-button {
    cursor: pointer;
    padding: 5px;
    width: 25px;
    height: 25px;
    border-radius: 0.3rem;
    margin-left: 0.2rem;
    background-color: #0056b3;
    fill: rgba(255, 255, 255, 0.8);
    color: rgba(255, 255, 255, 0.8);
}

</style>
