import pickle, json, numpy as np
from pathlib import Path
import pandas as pd
import threading
import webbrowser
import warnings
from functools import partial
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
import socket

try:
    from sklearn.exceptions import InconsistentVersionWarning
except Exception:
    InconsistentVersionWarning = None

if InconsistentVersionWarning is not None:
    warnings.filterwarnings("ignore", category=InconsistentVersionWarning)

BASE = str(Path(__file__).parent) + "/"
CARTE = "carte_arbres_interactive.html"
PORT = 8080


class SilentHTTPRequestHandler(SimpleHTTPRequestHandler):
    def log_message(self, format, *args):
        return


def Start_Carte():
    fichier_html = BASE + CARTE
    
    if not Path(fichier_html).exists():
        print(f"Carte introuvable : {fichier_html}")
        return None, None

    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(("127.0.0.1", 0))

    handler = partial(SilentHTTPRequestHandler, directory=str(BASE))
    serveur = ThreadingHTTPServer(("127.0.0.1", PORT), handler)
    thread = threading.Thread(target=serveur.serve_forever, daemon=True)
    thread.start()

    url = f"http://127.0.0.1:{PORT}/{CARTE}"
    #webbrowser.open(url)
    print(f"Carte ouverte : {url}")
    return serveur, thread

def charger_modele(k: int):
    with open(BASE + "scaler.pkl", "rb") as f:
        scaler = pickle.load(f)
    with open(BASE + f"model_k{k}.pkl", "rb") as f:
        model = pickle.load(f)
    with open(BASE + f"model_k{k}.json", encoding="utf-8") as f:
        meta = json.load(f)
    return scaler, model, {int(c): v for c, v in meta["label_map"].items()}, meta["algorithme"]


def predire(haut_tot, tronc_diam, k):
    scaler, model, label_map, algo = charger_modele(k)
    X_sc = scaler.transform(pd.DataFrame([[haut_tot, tronc_diam]], columns=['haut_tot', 'tronc_diam']))
    cluster = int(model.predict(X_sc)[0])
    return label_map[cluster], cluster, algo


if __name__ == "__main__":
    print("\n── Prédiction de catégorie d'arbre ──────────────────────────")
    serveur_http, _ = Start_Carte()

    while True:
        try:
            haut_tot   = float(input("Hauteur totale (m)      : "))
            tronc_diam = float(input("Diamètre du tronc (cm)  : "))
            print("\nChoix du cluster :")
            print("1. petit - grand")
            print("2. petit - moyen - grand")
            choix = int(input("Votre choix (1 ou 2)    : "))
            if choix == 1:
                k = 2
            elif choix == 2:
                k = 3
            else:
                print("Choix invalide\n")
                continue
        except ValueError:
            print("Valeur invalide\n")
            continue

        categorie, cluster, algo = predire(haut_tot, tronc_diam, k)

        print(f"\n  Catégorie : {categorie}")
        # print(f"  Cluster   : n°{cluster}")
        #print(f"  Algorithme: {algo}")
        print("─" * 50)

        again = input("\nNouvelle prédiction ? (o/n) : ").strip().lower()
        if again != "o":
            break

    if serveur_http is not None:
        serveur_http.shutdown()
        serveur_http.server_close()

    print("Au revoir.\n")