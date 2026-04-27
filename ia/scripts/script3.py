#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Script d'alerte tempête - Prédiction du risque de déracinement des arbres

Usage:
    python script_alerte_tempete.py -H <hauteur> -D <diametre> -s <situation> -f <feuillage> [options]

Exemple:
    python script_alerte_tempete.py -H 25 -D 45 -s Isolé -f Conifère -a 60
    python script_alerte_tempete.py -H 6 -D 180 -s Groupe -f Feuillu -a 20

Auteur: Allan Steed MIHINDOU
Date: 24-04-2026
Version: 1.0
"""

# ============================================================================
# PARTIE 1 : IMPORT DES BIBLIOTHÈQUES
# ============================================================================

import argparse
import sys
import json

# ============================================================================
# PARTIE 2 : DÉFINITION DES PARAMÈTRES (seuils et règles)
# ============================================================================

# Seuils calculés à partir des données de Saint-Quentin
SEUILS = {
    'hauteur_min': 14.0,      # mètres (75ème percentile)
    'diametre_max': 60,       # centimètres (25ème percentile)
    'age_min': 45             # années (75ème percentile)
}

# Correspondance score -> niveau de priorité
NIVEAUX_PRIORITE = {
    0: {"nom": "PRIORITÉ 4", "icone": "🟢", "action": "Inspection de routine uniquement"},
    1: {"nom": "PRIORITÉ 3", "icone": "🟡", "action": "Surveillance régulière suffisante"},
    2: {"nom": "PRIORITÉ 2", "icone": "🟠", "action": "À inspecter avant la prochaine tempête"},
    3: {"nom": "PRIORITÉ 1", "icone": "🔴", "action": "Inspection urgente recommandée"},
    4: {"nom": "PRIORITÉ 1", "icone": "🔴", "action": "Inspection urgente recommandée"},
    5: {"nom": "PRIORITÉ 1", "icone": "🔴", "action": "Inspection urgente recommandée"}
}

# ============================================================================
# PARTIE 3 : FONCTION DE PRÉDICTION
# ============================================================================

def calculer_score_risque(hauteur, diametre, situation, feuillage, age=None):
    """
    Calcule le score de risque d'un arbre (de 0 à 5).
    
    Paramètres :
        hauteur (float) : Hauteur totale de l'arbre en mètres
        diametre (float) : Diamètre du tronc en centimètres
        situation (str) : Situation de l'arbre ("Alignement", "Groupe" ou "Isolé")
        feuillage (str) : Type de feuillage ("Feuillu" ou "Conifère")
        age (float, optionnel) : Âge estimé en années
    
    Retourne :
        int : Score de risque de 0 à 5
    """
    score = 0
    
    # Règle 1 : Hauteur importante
    if hauteur >= SEUILS['hauteur_min']:
        score += 1
    
    # Règle 2 : Diamètre faible
    if diametre <= SEUILS['diametre_max']:
        score += 1
    
    # Règle 3 : Situation isolée
    if situation == 'Isolé':
        score += 1
    
    # Règle 4 : Feuillage conifère
    if feuillage == 'Conifère':
        score += 1
    
    # Règle 5 : Âge avancé (si fourni)
    if age is not None and age >= SEUILS['age_min']:
        score += 1
    
    return score


def predire_risque(hauteur, diametre, situation, feuillage, age=None):
    """
    Prédit le niveau de risque de déracinement d'un arbre.
    
    Paramètres :
        hauteur (float) : Hauteur totale de l'arbre en mètres
        diametre (float) : Diamètre du tronc en centimètres
        situation (str) : Situation de l'arbre ("Alignement", "Groupe" ou "Isolé")
        feuillage (str) : Type de feuillage ("Feuillu" ou "Conifère")
        age (float, optionnel) : Âge estimé en années
    
    Retourne :
        dict : Contenant le score, le niveau et la recommandation
    """
    # Validation des entrées
    situations_valides = ["Alignement", "Groupe", "Isolé"]
    feuillages_valides = ["Feuillu", "Conifère"]
    
    if situation not in situations_valides:
        raise ValueError(f"Situation invalide. Choisir parmi : {situations_valides}")
    
    if feuillage not in feuillages_valides:
        raise ValueError(f"Feuillage invalide. Choisir parmi : {feuillages_valides}")
    
    if hauteur < 0:
        raise ValueError("La hauteur ne peut pas être négative")
    
    if diametre < 0:
        raise ValueError("Le diamètre ne peut pas être négatif")
    
    # Calcul du score
    score = calculer_score_risque(hauteur, diametre, situation, feuillage, age)
    
    # Récupération du niveau associé
    niveau = NIVEAUX_PRIORITE[min(score, 5)]
    
    return {
        'score': score,
        'score_max': 5,
        'niveau': niveau['nom'],
        'icone': niveau['icone'],
        'recommandation': niveau['action']
    }


# ============================================================================
# PARTIE 4 : INTERFACE EN LIGNE DE COMMANDE
# ============================================================================

def afficher_resultat(resultat, args):
    """
    Affiche les résultats de la prédiction de manière lisible.
    """
    print("\n" + "=" * 60)
    print("🌳 SYSTÈME D'ALERTE TEMPÊTE - RÉSULTAT DE L'ANALYSE")
    print("=" * 60)
    
    print("\n📊 CARACTÉRISTIQUES DE L'ARBRE :")
    print(f"   • Hauteur totale    : {args.hauteur} m")
    print(f"   • Diamètre du tronc : {args.diametre} cm")
    print(f"   • Situation         : {args.situation}")
    print(f"   • Feuillage         : {args.feuillage}")
    if args.age is not None:
        print(f"   • Âge estimé        : {args.age} ans")
    else:
        print(f"   • Âge estimé        : Non renseigné")
    
    print("\n" + "-" * 60)
    print("🎯 ÉVALUATION DU RISQUE :")
    print(f"   • Score de risque   : {resultat['score']}/{resultat['score_max']}")
    print(f"   • Niveau d'alerte   : {resultat['icone']} {resultat['niveau']}")
    print(f"   • Recommandation    : {resultat['recommandation']}")
    
    print("\n" + "-" * 60)
    print("📋 FACTEURS DE RISQUE IDENTIFIÉS :")
    
    facteurs = []
    if args.hauteur >= SEUILS['hauteur_min']:
        facteurs.append(f"   • Hauteur ≥ {SEUILS['hauteur_min']} m (grand arbre)")
    if args.diametre <= SEUILS['diametre_max']:
        facteurs.append(f"   • Diamètre ≤ {SEUILS['diametre_max']} cm (arbre fin)")
    if args.situation == 'Isolé':
        facteurs.append("   • Situation isolée (pas de protection)")
    if args.feuillage == 'Conifère':
        facteurs.append("   • Feuillage persistant (prise au vent permanente)")
    if args.age is not None and args.age >= SEUILS['age_min']:
        facteurs.append(f"   • Âge ≥ {SEUILS['age_min']} ans (arbre âgé)")
    
    if facteurs:
        for f in facteurs:
            print(f)
    else:
        print("   • Aucun facteur de risque majeur détecté")
    
    print("\n" + "-" * 60)
    print("⚠️  LIMITES :")
    print("   • Ce système est une AIDE À LA DÉCISION")
    print("   • La décision finale revient aux agents terrain")
    print("   • À réévaluer avec l'expérience acquise")
    print("=" * 60)


def main():
    """
    Fonction principale - Parse les arguments et exécute la prédiction.
    """
    parser = argparse.ArgumentParser(
        description="Prédiction du risque de déracinement d'un arbre lors d'une tempête",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
EXEMPLES D'UTILISATION :
  # Arbre potentiellement à risque
  python script_alerte_tempete.py -H 25 -D 45 -s Isolé -f Conifère -a 60
  
  # Arbre sans risque apparent
  python script_alerte_tempete.py -H 6 -D 180 -s Groupe -f Feuillu -a 20
  
  # Sans renseigner l'âge
  python script_alerte_tempete.py -H 15 -D 70 -s Alignement -f Feuillu
  
  # Au format JSON (pour intégration)
  python script_alerte_tempete.py -H 25 -D 45 -s Isolé -f Conifère -a 60 --json
        """
    )
    
    # Arguments obligatoires
    parser.add_argument(
        "-H", "--hauteur", 
        type=float, 
        required=True,
        help="Hauteur totale de l'arbre (mètres)"
    )
    
    parser.add_argument(
        "-D", "--diametre", 
        type=float, 
        required=True,
        help="Diamètre du tronc (centimètres)"
    )
    
    parser.add_argument(
        "-s", "--situation", 
        type=str, 
        required=True,
        choices=["Alignement", "Groupe", "Isolé"],
        help="Situation de l'arbre"
    )
    
    parser.add_argument(
        "-f", "--feuillage", 
        type=str, 
        required=True,
        choices=["Feuillu", "Conifère"],
        help="Type de feuillage"
    )
    
    # Arguments optionnels
    parser.add_argument(
        "-a", "--age", 
        type=float, 
        default=None,
        help="Âge estimé de l'arbre (années) - optionnel"
    )
    
    parser.add_argument(
        "--json", 
        action="store_true",
        help="Afficher le résultat au format JSON"
    )
    
    parser.add_argument(
        "--version", 
        action="version", 
        version="%(prog)s 1.0"
    )
    
    # Parse des arguments
    args = parser.parse_args()
    
    try:
        # Prédiction du risque
        resultat = predire_risque(
            hauteur=args.hauteur,
            diametre=args.diametre,
            situation=args.situation,
            feuillage=args.feuillage,
            age=args.age
        )
        
        # Affichage des résultats
        if args.json:
            # Format JSON
            output = {
                "score": resultat['score'],
                "score_max": resultat['score_max'],
                "niveau": resultat['niveau'],
                "icone": resultat['icone'],
                "recommandation": resultat['recommandation'],
                "seuils": SEUILS
            }
            print(json.dumps(output, ensure_ascii=False, indent=2))
        else:
            # Format texte lisible
            afficher_resultat(resultat, args)
        
        # Code de retour (0 = succès)
        sys.exit(0)
        
    except ValueError as e:
        print(f"❌ Erreur de saisie : {e}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"❌ Erreur inattendue : {e}", file=sys.stderr)
        sys.exit(1)


# ============================================================================
# POINT D'ENTRÉE PRINCIPAL
# ============================================================================

if __name__ == "__main__":
    main()