import './bootstrap';
import Vue from 'vue';

/**
 * Vue Components
 *
 * The individual elements of the Cerberus UI. These are all individually loaded on a page by
 * page basis to reduce bundle size and prevent loading unwanted components on each page.
 *
 */
// Vue.component('BookListing', () => import('./components/BookListing.vue'));
// Vue.component('EditBook', () => import('./components/EditBook.vue'));


/**
 * Bootstrapping
 *
 * Build the Vue instance, import the Core Services at the point of creation and register all
 * of our Vue instance to the #root tag.
 */
const app = new Vue({
    el: '#app'
});
