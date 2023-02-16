<template>
    <section>
        <div class="row">
            {{ product }}
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Product Name</label>
                            <input type="text" v-model="product_name" placeholder="Product Name" class="form-control" :class="{ 'is-invalid': errors.title }">
                            <div class="invalid-feedback" v-if="errors.title">
                                {{ errors.title[0] }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">Product SKU</label>
                            <input type="text" v-model="product_sku" placeholder="Product SKU" class="form-control" :class="{ 'is-invalid': errors.sku }">
                            <div class="invalid-feedback" v-if="errors.sku">
                                {{ errors.sku[0] }}
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="">Description</label>
                            <textarea v-model="description" id="" cols="30" rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-2">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Media</h6>
                    </div>
                    <div class="card-body border">
                        <vue-dropzone ref="myVueDropzone" id="dropzone" :options="dropzoneOptions" @vdropzone-success="onSuccess"  @vdropzone-removed-file="onRemovedFile"></vue-dropzone>
                    </div>
                </div>
                <label for="">Old Photos:</label>
                <div v-if="old_images && old_images.length"  class="d-flex mb-3" style="gap: 10px;">
                    <div class="shadow p-1" v-for="(image, i) in old_images" :key="i">
                        <img :src="image" width="120px" :alt="image">
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" v-for="(item,index) in product_variant">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">Option</label>
                                    <select v-model="item.option" class="form-control">
                                        <option v-for="variant in variants"
                                                :value="variant.id">
                                            {{ variant.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label v-if="product_variant.length != 1" @click="product_variant.splice(index,1); checkVariant"
                                           class="float-right text-primary"
                                           style="cursor: pointer;">Remove</label>
                                    <label v-else for="">.</label>
                                    <input-tag v-model="item.tags" @input="checkVariant" class="form-control"></input-tag>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" v-if="product_variant.length < variants.length && product_variant.length < 3">
                        <button @click="newVariant" class="btn btn-primary">Add another option</button>
                    </div>

                    <div class="card-header text-uppercase">Preview</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <td>Variant</td>
                                    <td>Price</td>
                                    <td>Stock</td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="variant_price in product_variant_prices">
                                    <td>{{ variant_price.title }}</td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.price">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.stock">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            <div>
                <button @click="saveProduct" type="submit" class="btn btn-lg btn-primary">Save</button>
                <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
            </div>

            <div v-if="showToast" class="mx-2 flex-fill alert alert-success alert-dismissible fade show my-2" role="alert">
                <strong>Success!</strong> Product created successfully!!.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </section>
</template>

<script>
import vue2Dropzone from 'vue2-dropzone'
import 'vue2-dropzone/dist/vue2Dropzone.min.css'
import InputTag from 'vue-input-tag'

export default {
    components: {
        vueDropzone: vue2Dropzone,
        InputTag
    },
    props: {
        variants: {
            type: Array,
            required: true
        },
        product: {
            type: Object,
            required: true
        }
    },
    data() {
        return {
            product_name: '',
            product_sku: '',
            description: '',
            images: [],
            old_images: [],
            showToast: false,
            errors: {},
            product_variant: [
                {
                    option: this.variants[0].id,
                    tags: []
                }
            ],
            product_variant_prices: [],
            dropzoneOptions: {
                url: '/upload',
                thumbnailWidth: 150,
                maxFilesize: 2,
                addRemoveLinks: true,
                headers: {
                    'X-CSRF-TOKEN': document.head.querySelector('meta[name="csrf-token"]').content
                }
                // headers: {"My-Awesome-Header": "header value"}
            }
        }
    },
    methods: {
        onSuccess (file, response) {
            this.images.push(response.filename)
        },
        onRemovedFile (file, error, xhr) {
            const index = this.images.indexOf(file.name)
            if (index > -1) {
                this.images.splice(index, 1)
            }
        },
        // it will push a new object into product variant
        newVariant() {
            let all_variants = this.variants.map(el => el.id)
            let selected_variants = this.product_variant.map(el => el.option);
            let available_variants = all_variants.filter(entry1 => !selected_variants.some(entry2 => entry1 == entry2))
            // console.log(available_variants)

            this.product_variant.push({
                option: available_variants[0],
                tags: []
            })
        },

        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter((item) => {
                tags.push(item.tags);
            })

            this.getCombn(tags).forEach(item => {
                this.product_variant_prices.push({
                    title: item,
                    price: 0,
                    stock: 0
                })
            })
        },

        // combination algorithm
        getCombn(arr, pre) {
            pre = pre || '';
            if (!arr.length) {
                return pre;
            }
            let self = this;
            let ans = arr[0].reduce(function (ans, value) {
                return ans.concat(self.getCombn(arr.slice(1), pre + value + '/'));
            }, []);
            return ans;
        },

        // store product into database
        saveProduct() {
            this.errors = {}
            this.showToast = false
            let product = {
                title: this.product_name,
                sku: this.product_sku,
                description: this.description,
                product_image: this.images,
                product_variant: this.product_variant,
                product_variant_prices: this.product_variant_prices
            }


            axios.post('/product', product).then(response => {
                this.showToast = true
                console.log(response.data);
                this.product_name = ''
                this.product_sku= ''
                this.description= ''
                this.images= []
                this.product_variant= [
                    {
                        option: this.variants[0].id,
                        tags: []
                    }
                ]
                this.product_variant_prices= []
            }).catch(error => {
                if (error.response.status === 422) {
                    this.errors = error.response.data.errors;
                }
                console.log(error.response);
            })

            console.log(product);
        }


    },
    mounted() {
        this.product_name = this.product.title
        this.product_sku= this.product.sku
        this.description= this.product.description
        this.old_images = this.product.product_images.map(el => el.file_path)
        this.product.product_variant_prices.forEach(el => {
            this.product_variant_prices.push({
                title: el.title,
                price: el.price,
                stock: el.stock
            })
        })
    }
}
</script>
