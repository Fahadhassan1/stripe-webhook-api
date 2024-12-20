<template>
    <div>
      <h1>Posts</h1>
  
      <!-- Form for Create/Update -->
      <form @submit.prevent="savePost">
        <input v-model="post.title" placeholder="Post Title" required />
        <textarea v-model="post.content" placeholder="Post Content" required></textarea>
        <button type="submit">{{ editing ? 'Update' : 'Create' }} Post</button>
      </form>
  
      <ul>
        <li v-for="post in posts" :key="post.id">
          <h3>{{ post.title }}</h3>
          <p>{{ post.content }}</p>
          <button @click="editPost(post)">Edit</button>
          <button @click="deletePost(post.id)">Delete</button>
        </li>
      </ul>
    </div>
  </template>
  
  <script>
  import axios from 'axios';
  
  export default {
    data() {
      return {
        posts: [],
        post: { title: '', content: '' },
        editing: false,
        currentPostId: null,
      };
    },
    methods: {
      async fetchPosts() {
        try {
          const response = await axios.get('http://localhost/api/posts');
          this.posts = response.data;
        } catch (error) {
          console.error('Error fetching posts:', error);
        }
      },
  
      async savePost() {
        if (this.editing) {
          // Update post
          await axios.put(`http://localhost/api/posts/${this.currentPostId}`, this.post);
        } else {
          // Create post
          await axios.post('http://localhost/api/posts', this.post);
        }
        this.fetchPosts();
        this.resetForm();
      },
  
      async editPost(post) {
        this.post = { ...post };
        this.editing = true;
        this.currentPostId = post.id;
      },
  
      async deletePost(id) {
        await axios.delete(`http://localhost/api/posts/${id}`);
        this.fetchPosts();
      },
  
      resetForm() {
        this.post = { title: '', content: '' };
        this.editing = false;
        this.currentPostId = null;
      }
    },
    mounted() {
      this.fetchPosts();
    }
  };
  </script>
  