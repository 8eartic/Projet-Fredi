const categories = ['transport', 'hebergement', 'parking', 'carburant', 'autres_frais'];

function addJustificatif(categorie) {
    const container = document.getElementById(categorie + '_container');
    const row = document.createElement('div');
    row.className = 'justificatif-row';
    row.innerHTML = `
        <input type="file" name="${categorie}[]" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" style="margin-bottom: 8px;">
        <div style="display:flex;align-items:center;gap:4px;grid-column:2;">
            <input type="hidden" name="${categorie}_don[]" value="0">
            <input type="number" step="0.01" name="${categorie}_montant[]" placeholder="Montant (€)" style="flex:1;" onchange="calculTotals()">
            <label class="switch">
                <input type="checkbox" onchange="toggleDonationHidden(this)">
                <span class="slider"></span>
            </label>
            <span style="font-size:13px;color:#333;">Don</span>
        </div>
        <button type="button" onclick="this.parentElement.remove(); calculTotals();" style="background: red; color: white; padding: 6px 10px; margin-bottom: 8px; cursor: pointer; border: none; border-radius: 4px;">✕</button>
    `;
    container.appendChild(row);
}

function toggleDonationHidden(checkbox) {
    const row = checkbox.closest('.justificatif-row');
    if (!row) return;
    const hidden = row.querySelector('input[type="hidden"][name$="_don[]"]');
    if (hidden) {
        hidden.value = checkbox.checked ? '1' : '0';
    }
}

function calculTotals() {
    let grandTotal = 0;

    categories.forEach(categorie => {
        const montants = document.querySelectorAll(`input[name="${categorie}_montant[]"]`);
        let total = 0;
        montants.forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        
        // Ajouter les montants des documents existants (affichés dans .existing-docs)
        const existingDocs = document.querySelectorAll(`#${categorie}_container .existing-docs li`);
        existingDocs.forEach(li => {
            const match = li.textContent.match(/\(([\d.,]+)\s*€\)/);
            if (match) {
                total += parseFloat(match[1].replace(',', '.')) || 0;
            }
        });
        
        document.getElementById('total_' + categorie).textContent = total.toFixed(2);
        grandTotal += total;
    });

    document.getElementById('total').textContent = grandTotal.toFixed(2);
    document.getElementById('total_input').value = grandTotal.toFixed(2);
}

// Initialiser les écouteurs
document.querySelectorAll('input[type="number"]').forEach(input => {
    if (input.name.includes('_montant')) {
        input.addEventListener('change', calculTotals);
        input.addEventListener('input', calculTotals);
    }
});

// Calcul initial
calculTotals();