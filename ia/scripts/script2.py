#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Exemple :
python predict_age_default.py --model models/model_age_classification_pipeline.pkl --haut_tot 18 --tronc_diam 45
"""

import argparse
import pickle
from pathlib import Path

import pandas as pd


def str_to_bool(value: str) -> int:
    """Convertit une réponse texte en binaire 0/1."""
    if value is None:
        return 0
    return 1 if str(value).strip().lower() in ["oui", "yes", "true", "1", "vrai"] else 0


def build_input_dataframe(args: argparse.Namespace) -> pd.DataFrame:
    """Construit le DataFrame attendu par le pipeline entraîné."""

    remarquable_bin = str_to_bool(args.remarquable)

    ratio_tronc_haut = args.haut_tronc / args.haut_tot if args.haut_tot != 0 else 0
    ratio_diam_haut = args.tronc_diam / args.haut_tot if args.haut_tot != 0 else 0

    input_data = pd.DataFrame({
        "haut_tot": [args.haut_tot],
        "haut_tronc": [args.haut_tronc],
        "tronc_diam": [args.tronc_diam],
        "clc_nbr_diag": [args.clc_nbr_diag],

        "clc_quartier": [args.clc_quartier],
        "clc_secteur": [args.clc_secteur],
        "fk_arb_etat": [args.fk_arb_etat],
        "fk_stadedev": [args.fk_stadedev],
        "fk_situation": [args.fk_situation],
        "feuillage": [args.feuillage],
        "remarquable": [args.remarquable],
        "nomfrancais": [args.nomfrancais],

        "remarquable_bin": [remarquable_bin],
        "ratio_tronc_haut": [ratio_tronc_haut],
        "ratio_diam_haut": [ratio_diam_haut],
    })

    return input_data


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Prédiction de la classe d'âge d'un arbre à partir d'un modèle entraîné."
    )

    parser.add_argument(
        "--model",
        default="models/model_age_classification_pipeline.pkl",
        help="Chemin vers le modèle .pkl sauvegardé."
    )

    # Variables numériques avec valeurs par défaut
    parser.add_argument("--haut_tot", type=float, default=10.0, help="Hauteur totale de l'arbre.")
    parser.add_argument("--haut_tronc", type=float, default=3.0, help="Hauteur du tronc.")
    parser.add_argument("--tronc_diam", type=float, default=25.0, help="Diamètre du tronc.")
    parser.add_argument("--clc_nbr_diag", type=float, default=0.0, help="Nombre de diagnostics.")

    # Variables catégorielles avec valeurs par défaut
    parser.add_argument("--clc_quartier", default="Centre Ville", help="Quartier de l'arbre.")
    parser.add_argument("--clc_secteur", default="Centre", help="Secteur de l'arbre.")
    parser.add_argument("--fk_arb_etat", default="En place", help="État de l'arbre.")
    parser.add_argument("--fk_stadedev", default="Adulte", help="Stade de développement.")
    parser.add_argument("--fk_situation", default="Isole", help="Situation de l'arbre.")
    parser.add_argument("--feuillage", default="Caduc", help="Type de feuillage.")
    parser.add_argument("--remarquable", default="Non", help="Arbre remarquable : Oui ou Non.")
    parser.add_argument("--nomfrancais", default="Erable plane", help="Nom français de l'arbre.")

    parser.add_argument(
        "--show-input",
        action="store_true",
        help="Affiche les données utilisées pour la prédiction."
    )

    args = parser.parse_args()

    model_path = Path(args.model)

    if not model_path.exists():
        raise FileNotFoundError(
            f"Modèle introuvable : {model_path}\n"
            "Vérifie le chemin du fichier .pkl."
        )

    with open(model_path, "rb") as f:
        model = pickle.load(f)

    input_data = build_input_dataframe(args)

    if args.show_input:
        print("\nDonnées utilisées pour la prédiction :")
        print(input_data.to_string(index=False))

    try:
        prediction = model.predict(input_data)[0]

        print("\n===== Résultat de la prédiction =====")
        print(f"Classe d'âge prédite : {prediction}")

        if hasattr(model, "predict_proba"):
            try:
                probabilities = model.predict_proba(input_data)[0]
                classes = model.classes_

                print("\nProbabilités par classe :")
                for classe, proba in zip(classes, probabilities):
                    print(f"- {classe} : {proba:.2%}")
            except Exception:
                pass

    except Exception as e:
        print("\nErreur lors de la prédiction :")
        print(e)
        print("\nColonnes envoyées au modèle :")
        print(list(input_data.columns))


if __name__ == "__main__":
    main()
