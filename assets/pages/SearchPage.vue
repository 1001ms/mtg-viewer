<script setup>
import { ref, watch, onMounted } from 'vue';
import { searchByNameCard } from '../services/cardService';

const searchQuery = ref('');
const cards = ref([]);
const loadingCards = ref(false);
const page = ref(1);
const totalPages = ref(10); // À ajuster selon le backend

async function fetchCards() {
    if (searchQuery.value.length >= 3) {
        loadingCards.value = true;
        try {
            const response = await searchByNameCard(searchQuery.value, page.value);
            cards.value = response.cards;
            totalPages.value = Math.ceil(response.total / 20); // Ajuster selon la réponse du backend
        } catch (error) {
            console.error('Erreur lors de la recherche', error);
        } finally {
            loadingCards.value = false;
        }
    } else {
        cards.value = [];
    }
}

watch([searchQuery, page], fetchCards);
</script>

<template>
    <div>
        <h1>Rechercher une Carte</h1>
        <input v-model="searchQuery" placeholder="Entrez un nom de carte..." />
    </div>

    <div class="card-list">
        <div v-if="loadingCards">Chargement...</div>
        <div v-else>
            <div class="card" v-for="card in cards" :key="card.id">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }"> 
                    {{ card.name }} - {{ card.uuid }}
                </router-link>
            </div>
        </div>
    </div>

    <div class="pagination">
        <button @click="page--" :disabled="page <= 1">Précédent</button>
        <span>Page {{ page }} / {{ totalPages }}</span>
        <button @click="page++" :disabled="page >= totalPages">Suivant</button>
    </div>
</template>
