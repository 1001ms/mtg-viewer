export async function fetchAllCards(page = 1, limit = 100) {
    const response = await fetch(`/api/card/all/${page}/${limit}`);
    if (!response.ok) throw new Error('Failed to fetch cards');
    return await response.json();
}


export async function fetchCard(uuid) {
    const response = await fetch(`/api/card/${uuid}`);
    if (response.status === 404) return null;
    if (!response.ok) throw new Error('Failed to fetch card');
    const card = await response.json();
    card.text = card.text.replaceAll('\\n', '\n');
    return card;
}

export async function searchByNameCard(name, page = 1, limit = 20) {
    const response = await fetch(`/api/card/search/${encodeURIComponent(name)}/${page}/${limit}`);
    if (!response.ok) throw new Error('Failed to fetch cards');
    return await response.json();
}
