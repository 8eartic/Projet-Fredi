# 📋 GUIDE RAPIDE - Comment utiliser FREDI

**Date**: 22 Avril 2026  
**Version**: SP3 (Trésorier + CERFA)

---

## 🎯 WORKFLOW COMPLET

### **POUR LES ADHÉRENTS (Membres du club)**

#### 1. **Connexion**
- Aller sur `votre-site.epizy.com`
- Email: `votre-email@domain.com`
- Mot de passe: `votre-mot-de-passe`
- → Redirection automatique vers le formulaire

#### 2. **Créer un rapport de frais**
- Page: `Formulaire_remboursement.php`
- Remplir:
  - **Titre**: "Frais déplacement - [événement]"
  - **Documents**: Photos/factures des dépenses
  - **Catégories**: repas_france, transport, hebergement, etc.
  - **Montants**: Prix TTC de chaque dépense

#### 3. **Soumettre le rapport**
- Cliquer "📤 Soumettre la demande"
- Status passe de "brouillon" → "soumis"
- Le trésorier reçoit automatiquement la notification

---

### **POUR LES TRÉSORIERS**

#### 1. **Connexion**
- Aller sur `votre-site.epizy.com`
- Email: `tresorier@votre-domaine.epizy.com`
- Mot de passe: `password123`
- → Redirection automatique vers le dashboard

#### 2. **Voir les rapports en attente**
- Dashboard montre:
  - Nombre total de rapports
  - Rapports soumis (à valider)
  - Rapports validés
  - Montants totaux
- Liste des bordereaux "soumis"

#### 3. **Valider un rapport**
- Cliquer sur un rapport dans la liste
- Page détail s'ouvre avec:
  - Infos de l'adhérent
  - Tous les documents/justificatifs
  - Montants proposés

#### 4. **Validation ligne par ligne**
- Pour chaque document:
  - Vérifier le montant
  - Changer si nécessaire
  - Sélectionner "accepté" ou "rejeté"
  - Cliquer "Valider cette ligne"

#### 5. **Validation finale**
- Quand toutes les lignes sont validées:
  - Cliquer "✅ Valider le Bordereau Complet"
  - Status passe à "valide"
  - Rapport prêt pour CERFA

#### 6. **Générer le CERFA**
- Dans le détail du rapport validé:
  - Bouton "Generate CERFA" apparaît
  - Cliquer → PDF s'ouvre
  - Imprimer ou sauvegarder en PDF
  - Format officiel CERFA 11580-02

#### 7. **Rapports comptables**
- Bouton "Rapports" dans le dashboard
- Voir statistiques par:
  - Catégorie (repas, transport, etc.)
  - Ligue/club
  - Mois/année
- Exporter en CSV pour Excel

---

## 🔑 CODES D'ACCÈS DE TEST

### **Trésorier de test**
```
Email: tresorier@votre-domaine.epizy.com
Mot de passe: password123
Rôle: tresorier
```

### **Adhérent de test** (si vous en créez un)
```
Email: adherent@votre-domaine.epizy.com
Mot de passe: password123
Rôle: adherent
```

---

## 📂 STRUCTURE DES FICHIERS

### **Pages principales**
- `index.php` - Page d'accueil/connexion
- `Formulaire_remboursement.php` - Création rapports (adhérents)
- `tresorier_dashboard.php` - Dashboard trésorier
- `tresorier_detail.php` - Validation rapports
- `cerfa_generator.php` - Génération PDF CERFA
- `tresorier_reporting.php` - Rapports comptables

### **Authentification**
- `auth_login.php` - Traitement connexion
- `auth_logout.php` - Déconnexion
- `auth_register.php` - Inscription

### **Base de données**
- `remboursement` - Rapports principaux
- `documents` - Justificatifs attachés
- `users` - Comptes utilisateurs
- `validation_history` - Historique validations (SP3)

---

## ⚠️ POINTS IMPORTANTS

### **Rôles utilisateur**
- `adherent` → Formulaire_remboursement.php
- `tresorier` → tresorier_dashboard.php
- `admin` → Accès complet

### **États des rapports**
- `brouillon` → En cours d'édition
- `soumis` → Envoyé au trésorier
- `en_revision` → Trésorier examine
- `valide` → Approuvé, CERFA disponible
- `rejete` → Refusé

### **Documents acceptés**
- PDF, JPG, JPEG, PNG
- Taille max: 30 Mo
- Catégories: repas_france, transport, hebergement, etc.

---

## 🆘 DÉPANNAGE

### **Problème: Bouton déconnexion ne marche pas**
**Solution**: Liens corrigés vers `auth_logout.php`

### **Problème: Redirection après login**
**Solution**:
- `adherent` → `Formulaire_remboursement.php`
- `tresorier` → `tresorier_dashboard.php`

### **Problème: CERFA ne se génère pas**
**Solution**: Rapport doit être status "valide"

### **Problème: Pas de rapports dans dashboard**
**Solution**: Adhérents doivent soumettre des rapports

---

## 📞 SUPPORT

Si problème:
1. Vérifier les logs PHP
2. Vérifier la base de données
3. Tester avec compte de test
4. Contact: [votre email]

---

**FREDI SP3 - Guide Utilisateur**  
*Maison des Ligues de Lorraine*  
*22 Avril 2026*
