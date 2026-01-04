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
├── database/           # Interface utilisateur (HTML/CSS/JS)
├── back/           # API + logique serveur (PHP)
├── front/       # Fichier SQL (structure + données)
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

👤 Comptes de démonstration

Administrateur
	•	Email : admin@demo.fr
	•	Mot de passe : Passw0rd!!!!!!

Employé
	•	Email : employe@demo.fr
	•	Mot de passe : Passw0rd!!!!!!

Client
	•	Email : client@demo.fr
	•	Mot de passe : Passw0rd!!!!!!

🔐 Sécurité mise en place
	•	Hash des mots de passe (password_hash)
	•	Authentification par token
	•	Vérification des rôles (admin / employé / client)
	•	Requêtes préparées PDO (protection SQL Injection)
	•	Validation des données côté serveur
	•	Accès API protégé par Authorization Header


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
	•	Application déployée : (lien à compléter)
	•	Dépôt GitHub public : https://github.com/worldvcg/vite_gourmand   

    📋 Gestion de projet
	•	Méthode : Kanban
	•	Outil : Trello : https://trello.com/invite/b/68f9eddc36985c423b501f20 ATTI4f96529100442d33b3f2d1f27d62df482A0844A8/vite-gourmand-ecf
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


👨‍💻 Auteur

Jean-Baptiste Lanusse
Projet réalisé dans le cadre de l’ECF Studi – Développement Web
