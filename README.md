Vite & Gourmand

Application web de restauration événementielle
Projet ECF – Studi


📌 Présentation du projet

Vite & Gourmand est une application web permettant :
	•	de consulter des menus,
	•	de passer une commande de prestation,
	•	de suivre l’état d’une commande,
	•	de laisser un avis,
	•	de gérer les menus et commandes côté employé,
	•	d’administrer les utilisateurs et contenus.

L’application respecte une architecture front / back, avec gestion des rôles et sécurité côté serveur.


🛠️ Technologies utilisées

Front-end
	•	HTML5
	•	CSS3 / Bootstrap 5
	•	JavaScript (vanilla)

Back-end
	•	PHP 8 (PDO)
	•	MySQL
	•	Architecture MVC simplifiée
	•	API REST interne (index.php?route=...)

Outils
	•	MAMP (macOS)
	•	phpMyAdmin
	•	Git / GitHub

📂 Structure du projet

vite_gourmand/
│
├── front/              # Interface utilisateur (HTML / CSS / JS)
├── back/               # API + logique serveur (PHP)
├── database/           # Fichier SQL (structure + données)
├── README.md
└── .gitignore


⚙️ Installation en local (macOS + MAMP)

1️⃣ Prérequis
	•	macOS
	•	MAMP installé (Apache + MySQL)
	•	Navigateur web récent

2️⃣ Placement du projet

/Applications/MAMP/htdocs/vite_gourmand

3️⃣ Démarrer MAMP
	•	Lancer MAMP
	•	Démarrer Apache et MySQL
	•	Vérifier :
	•	Apache : localhost:8888
	•	MySQL : port par défaut MAMP

4️⃣ Création de la base de données
	1.	Ouvrir phpMyAdmin
	2.	Créer une base de données : vite_gourmand
    3.	Importer le fichier SQL : database/vite_gourmand.sql

5️⃣ Configuration base de données

Vérifier le fichier : back/config/db.php
Paramètres par défaut MAMP :
host     = localhost
dbname   = vite_gourmand
user     = root
password = root
port     = 8889

6️⃣ Accès à l’application

Front: http://localhost:8888/vite_gourmand/front/index.html
API (exemple): http://localhost:9000/index.php?route=/api/menus

L’API est servie via le serveur PHP intégré sur le port 9000 afin de séparer le front et le back.

🔐 Sécurité mise en place
	•	Hash des mots de passe (password_hash)
	•	Authentification par token
	•	Vérification des rôles (admin / employé / client)
	•	Requêtes préparées PDO (protection SQL Injection)
	•	Validation des données côté serveur
	•	Accès API protégé par Authorization Header

⚠️ Paiement
Dans le cadre de ce projet ECF, aucun système de paiement réel n’est intégré.
Le choix du mode de paiement est simulé afin de respecter le périmètre pédagogique,
tout en conservant une structure évolutive vers une solution réelle (Stripe, PayPal).


🧪 Fonctionnalités principales

Visiteur
	•	Consulter les menus
	•	Envoyer un message via le formulaire de contact

Client
	•	Créer une commande
	•	Modifier / annuler une commande (selon statut)
	•	Suivre une commande
	•	Laisser un avis

Employé
	•	Gestion CRUD des menus
	•	Consultation des commandes

Administrateur
	•	Gestion des utilisateurs
	•	Modération des avis
	•	Accès aux statistiques


🌍 Déploiement
	•	Application déployée : https://jean-baptiste.alwaysdata.net/
	•	Dépôt GitHub public : https://github.com/worldvcg/vite_gourmand 

    📋 Gestion de projet
	•	Méthode : Kanban
	•	Outil : Trello
	•	Lien : https://trello.com/invite/b/68f9eddc36985c423b501f20/ATTI4f96529100442d33b3f2d1f27d62df482A0844A8/vite-gourmand-ecf 
	•	Fonctionnalités développées par branches :
	•	develop
	•	feature/*
	•	merge vers main après validation

📄 Livrables fournis
	•	Code source (GitHub public)
	•	Base de données SQL
	•	Manuel utilisateur (PDF)
	•	Charte graphique (PDF)
	•	Documentation technique (PDF)
	•	Documentation gestion de projet (PDF)

La charte graphique du projet Vite & Gourmand ainsi que l’ensemble des maquettes (wireframes et mockups) sont regroupées dans un document PDF unique.

✅ Mise à jour (janvier 2026)

- Ajout d’une structure back-end plus claire (séparation Controller / Service / Repository) afin de mieux respecter les responsabilités de chaque couche et améliorer la maintenabilité.
- Ajout d’un environnement Docker (optionnel) pour lancer rapidement l’application en local sans dépendre de MAMP.

⚠️ Docker est fourni uniquement pour faciliter l’installation en local.
Le déploiement en production est effectué sur AlwaysData (PHP/MySQL).

👨‍💻 Auteur

Ce projet démontre la capacité à concevoir, développer, sécuriser et déployer
une application web complète, conforme à un cahier des charges, dans un contexte professionnel.

Jean-Baptiste Lanusse
Projet réalisé dans le cadre de l’ECF Studi – Développement Web
