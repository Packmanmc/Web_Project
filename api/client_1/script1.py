import pickle, json, numpy as np
from pathlib import Path
import pandas as pd
import sys

try:
    from sklearn.exceptions import InconsistentVersionWarning
    import warnings
    warnings.filterwarnings("ignore", category=InconsistentVersionWarning)
except Exception:
    pass

BASE = str(Path(__file__).parent) + "/"

def charger_modele(k: int):
    with open(BASE + "scaler.pkl", "rb") as f:
        scaler = pickle.load(f)
    with open(BASE + f"model_k{k}.pkl", "rb") as f:
        model = pickle.load(f)
    with open(BASE + f"model_k{k}.json", encoding="utf-8") as f:
        meta = json.load(f)
    return scaler, model, {int(c): v for c, v in meta["label_map"].items()}, meta["algorithme"]


def predire(haut_tot, tronc_diam, k):
    """Prédire la catégorie de l'arbre."""
    scaler, model, label_map, algo = charger_modele(k)
    X_sc = scaler.transform(pd.DataFrame([[haut_tot, tronc_diam]], columns=['haut_tot', 'tronc_diam']))
    cluster = int(model.predict(X_sc)[0])
    return label_map[cluster], cluster, algo


if __name__ == "__main__":
    # python3 script1.py <hauteur> <diameter> <k>
    if len(sys.argv) >= 4:
        try:
            haut_tot = float(sys.argv[1])
            tronc_diam = float(sys.argv[2])
            k = int(sys.argv[3])
            
            if k not in [2, 3]:
                print(json.dumps({"error": "k doit être 2 ou 3", "status": "error"}))
                sys.exit(1)
            
            categorie, cluster, algo = predire(haut_tot, tronc_diam, k)
            
            result = {
                "status": "success",
                "categorie": categorie,
                "cluster": cluster,
                "hauteur_tot": haut_tot,
                "tronc_diam": tronc_diam,
                "k": k,
                "algorithme": algo
            }
            print(json.dumps(result, ensure_ascii=False))
        except Exception as e:
            print(json.dumps({"error": str(e), "status": "error"}, ensure_ascii=False))
            sys.exit(1)
    