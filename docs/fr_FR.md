# Documentation — Plugin Releases pour GLPI

## Présentation

Le plugin **Releases** permet de gérer les mises en production de changements au sein de GLPI. Il s'intègre au processus ITIL en associant des changements, des articles de base de connaissances et des acteurs à chaque mise en production.

**Licence** : GPLv2+  
**Auteurs** : Xavier Caillaud, Alban Lesellier, Infotel  
**Dépôt** : https://github.com/InfotelGLPI/releases

---

## Fonctionnalités

- Création et suivi de mises en production liées à des changements GLPI
- Association de changements, articles KB, documents et éléments d'inventaire
- Gestion des risques, plans de retour arrière (rollback), tâches de déploiement et tests
- Validation via une étape de revue et de finalisation
- Notifications par e-mail (création, mise à jour, clôture, suppression)
- Modèles de mises en production (templates) réutilisables
- Intégration au planning GLPI (tâches de déploiement planifiées)
- Intégration avec le plugin **Metademands** et le plugin **Mydashboard**
- Gestion des droits par profil
- Suivi de l'historique complet

---

## Prérequis

| Composant | Version minimale |
|-----------|-----------------|
| GLPI      | 11.0            |
| PHP       | ≥ 8.1           |

---

## Installation

1. Télécharger l'archive depuis https://github.com/InfotelGLPI/releases/releases
2. Décompresser dans le dossier `marketplace/` de votre instance GLPI
3. Accéder à **Configuration > Plugins** dans GLPI
4. Cliquer sur **Installer** puis **Activer** pour le plugin Releases

---

## Droits et profils

Les droits se configurent dans **Administration > Profils**, onglet **Releases**.

| Droit | Clé interne |
|-------|------------|
| Releases | `plugin_releases_releases` |
| Risques | `plugin_releases_risks` |
| Tâches | `plugin_releases_tasks` |

---

## Cycle de vie d'une mise en production

Une mise en production passe par les statuts suivants dans l'ordre :

| Statut | Description |
|--------|-------------|
| **Nouveau** | Mise en production créée, aucune information renseignée |
| **Périmètre défini** | La description du périmètre a été saisie |
| **Dates définies** | Les dates de pré-production et de production sont renseignées |
| **Changements définis** | Les changements associés sont liés |
| **Risques définis** | Les risques ont été saisis |
| **Retours arrière définis** | Les plans de rollback ont été saisis |
| **Tâches en cours** | Les tâches de déploiement sont en cours |
| **Tests en cours** | Les tests sont en cours |
| **À finaliser** | Prêt pour la finalisation |
| **Revue effectuée** | La revue post-déploiement a été complétée |
| **Terminé** | Mise en production clôturée avec succès |
| **Échoué** | La mise en production a échoué |

Le statut avance automatiquement selon les données saisies (dates, description, etc.).

---

## Création d'une mise en production

### Depuis le menu principal

1. Aller dans **Assistance > Releases** (interface centrale)
2. Cliquer sur **+ Ajouter**
3. Renseigner les champs obligatoires :
   - **Titre** : nom de la mise en production
   - **Périmètre** : description des changements à déployer
4. Sauvegarder

### Depuis un changement

1. Ouvrir un changement existant
2. Aller sur l'onglet **Releases**
3. Cliquer sur **+ Ajouter une release**

### Depuis un modèle

Lors de la création, sélectionner un **modèle de release** dans le formulaire pour pré-remplir les risques, tests, tâches et rollbacks définis dans ce modèle.

---

## Champs du formulaire principal

| Champ | Description |
|-------|-------------|
| **Titre** | Nom de la mise en production |
| **Statut** | Avancement dans le cycle de vie |
| **Périmètre** | Description des éléments à déployer (éditeur riche) |
| **Date de pré-production** | Date planifiée pour le passage en pré-prod |
| **Date de production** | Date planifiée pour le passage en production |
| **Arrêt de service** | Indique si un arrêt est nécessaire |
| **Détails arrêt de service** | Description de l'arrêt (horaires, impact…) |
| **Communication** | Nécessite une communication aux utilisateurs |
| **Type de communication** | Cible : Entité, Groupe, Profil, Utilisateur, Localisation |
| **Cibles** | Sélection des destinataires de la communication |
| **Localisation** | Lieu physique concerné |
| **Entité** | Entité GLPI de la mise en production |

---

## Onglets disponibles

### Changements
Lie la mise en production à des changements GLPI existants.

### Documents
Attache des fichiers documentaires à la release.

### Base de connaissances
Associe des articles KB pertinents.

### Éléments
Associe des éléments de l'inventaire GLPI (matériel, logiciels…).

### Finalisation
Permet de saisir le compte-rendu de la mise en production (résultat, observations).

### Revue
Permet d'effectuer la revue post-déploiement (bilan, actions correctives).

### Notes
Bloc-notes libre attaché à la release.

### Journal
Historique complet des modifications.

---

## Timeline (ligne de temps)

La timeline affiche chronologiquement tous les éléments de la release. Depuis la timeline, on peut ajouter :

### Risques
- **Titre** : intitulé du risque
- **Type** : classification du risque
- **Description** : détail du risque identifié
- **État** : À faire / Fait

### Retours arrière (Rollback)
- **Titre** : intitulé du plan de retour arrière
- **Type** : classification
- **Description** : procédure de retour arrière
- **État** : À faire / Fait

### Tâches de déploiement
- **Titre** : intitulé de la tâche
- **Type** : classification
- **Description** : procédure à exécuter
- **Niveau** : ordre d'exécution (priorité)
- **Risque associé** : risque que cette tâche adresse
- **Planning** : dates de début/fin, technicien assigné
- **État** : À faire / Fait / Échoué

### Tests
- **Titre** : intitulé du test
- **Type** : classification du test
- **Description** : procédure de test
- **Risque associé** : risque que ce test vérifie
- **État** : À faire / Fait / Échoué

### Suivis
Commentaires libres sur l'avancement de la mise en production.

---

## Modèles de release (Templates)

Les modèles permettent de pré-configurer une release type réutilisable.

**Accès** : **Assistance > Modèles de releases**

Un modèle définit :
- Les informations générales (titre, périmètre, paramètres communication)
- Les risques pré-définis
- Les rollbacks pré-définis
- Les tâches de déploiement pré-définies
- Les tests pré-définis
- Les acteurs pré-assignés (utilisateurs, groupes, fournisseurs)
- Les éléments d'inventaire associés

À la création d'une release depuis un modèle, tous ces éléments sont automatiquement copiés.

---

## Finalisation

L'onglet **Finalisation** permet de clore officiellement la mise en production :

- Renseigner le compte-rendu de déploiement
- Marquer la release comme **Réussie** (statut → Terminé) ou **Échouée** (statut → Échoué)

---

## Revue post-déploiement

L'onglet **Revue** permet d'effectuer le bilan après la mise en production :

- Observations sur le déroulement
- Actions correctives à mener
- La revue fait passer le statut à **Revue effectuée**

---

## Notifications

Le plugin envoie des notifications par e-mail lors des événements suivants :

| Événement | Description |
|-----------|-------------|
| **Nouvelle release** | Envoyée à la création |
| **Mise à jour** | Envoyée à chaque modification |
| **Clôture** | Envoyée à la clôture |
| **Suppression** | Envoyée à la suppression |

La configuration se fait dans **Configuration > Notifications** en filtrant sur le type **Release**.

---

## Intégrations

### Changements GLPI
Un onglet **Releases** apparaît dans chaque fiche de Changement pour lister et créer les releases associées.

### Planning GLPI
Les tâches de déploiement avec date planifiée apparaissent dans le planning central et personnel de GLPI.

### Metademands
Si le plugin Metademands est installé, le type `Release` peut être utilisé dans les méta-demandes.

### Mydashboard
Si le plugin Mydashboard est installé, un widget d'alerte Release est disponible dans les tableaux de bord.

---

## Traductions disponibles

| Langue | Code |
|--------|------|
| Français | fr_FR |
| Anglais | en_GB |
| Allemand | de_DE |
| Espagnol (Équateur) | es_EC |
| Portugais (Brésil) | pt_BR |
| Tchèque | cs_CZ |
| Turc | tr_TR |

Contribuer aux traductions : https://explore.transifex.com/infotelGLPI/GLPI_releases/

---

## Support et contribution

- **Signaler un bug** : https://github.com/InfotelGLPI/releases/issues
- **Dépôt source** : https://github.com/InfotelGLPI/releases
- **Blog Infotel** : https://blogglpi.infotel.com
