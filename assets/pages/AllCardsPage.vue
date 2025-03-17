<script setup>
import { onMounted, ref, watch } from 'vue';
import { fetchAllCards } from '../services/cardService';

const cards = ref([]);
const loadingCards = ref(true);
const page = ref(1);
const totalPages = ref(1);
const limit = 100; // Nombre de cartes par page

async function loadCards() {
    loadingCards.value = true;
    try {
        const response = await fetchAllCards(page.value, limit);
        cards.value = response.cards;
        totalPages.value = Math.ceil(response.total / limit); // Calculer le nombre total de pages
    } catch (error) {
        console.error('Erreur lors du chargement des cartes', error);
    } finally {
        loadingCards.value = false;
    }
}

// Recharger les cartes lorsqu'on change de page
watch(page, loadCards);

onMounted(() => {
    loadCards();
});
</script>

<template>
    <div>
        <h1>Toutes les cartes</h1>
    </div>

    <div class="card-list">
        <div v-if="loadingCards">Loading...</div>
        <div v-else>
            <div class="card-result" v-for="card in cards" :key="card.id">
                <router-link :to="{ name: 'get-card', params: { uuid: card.uuid } }">
                    {{ card.name }} <span>({{ card.uuid }})</span>
                </router-link>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <button @click="page--" :disabled="page <= 1">Précédent</button>
        <span>Page {{ page }} / {{ totalPages }}</span>
        <button @click="page++" :disabled="page >= totalPages">Suivant</button>
    </div>
</template>

<style scoped>
.pagination {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

button {
    margin: 0 10px;
    padding: 8px 12px;
    background: #007bff;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 5px;
}

button:disabled {
    background: #ccc;
    cursor: not-allowed;
}
</style>
