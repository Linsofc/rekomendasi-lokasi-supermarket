from flask import Flask, render_template, request, redirect, url_for
import pymysql
import pymysql.cursors
import pandas as pd
import math
import time
import matplotlib
matplotlib.use("Agg") # Memastikan Matplotlib berjalan di background (tanpa GUI desktop)
import matplotlib.pyplot as plt
import io
import base64

app = Flask(__name__)

# ==========================================
# FUNGSI MODUL A, B, dan C
# ==========================================
def get_db_connection():
    return pymysql.connect(
        host='localhost',
        user='root',
        password='',
        database='db_lokasi',
        cursorclass=pymysql.cursors.DictCursor
    )

def load_and_preprocess_data():
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            cursor.execute("SELECT * FROM lokasi")
            data = cursor.fetchall()
            
        for row in data:
            row["cost"] = int(row["biaya_pembangunan"])
            row["value"] = int((row["kepadatan_penduduk"] * 0.6) + (row["daya_beli"] * 0.4))
            row["latitude"] = float(row["latitude"])
            row["longitude"] = float(row["longitude"])
        return data
    except Exception as e:
        print(e)
        return []
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

def cekJarakAman(lokasiBaru, daftarTerpilih, batasJarak):
    for loc in daftarTerpilih:
        jarak = math.sqrt((lokasiBaru["latitude"] - loc["latitude"])**2 + 
                          (lokasiBaru["longitude"] - loc["longitude"])**2)
        jarakKm = jarak * 111 
        if jarakKm < batasJarak:
            return False
    return True

def jalankanGreedy(daftarLokasi, maxBudget, batasJarak):
    for loc in daftarLokasi:
        loc["rasio"] = loc["value"] / loc["cost"]
    lokasiSorted = sorted(daftarLokasi, key=lambda x: x["rasio"], reverse=True)
    
    lokasiTerpilih = []
    totalCost = 0
    totalValue = 0
    for loc in lokasiSorted:
        if totalCost + loc["cost"] <= maxBudget:
            if cekJarakAman(loc, lokasiTerpilih, batasJarak):
                lokasiTerpilih.append(loc)
                totalCost += loc["cost"]
                totalValue += loc["value"]
    return {"metode": "Greedy", "lokasiTerpilih": lokasiTerpilih, "totalCost": totalCost, "totalValue": totalValue}

def jalankanDp(daftarLokasi, maxBudget):
    n = len(daftarLokasi)
    dpTable = [[0 for _ in range(maxBudget + 1)] for _ in range(n + 1)]
    for i in range(1, n + 1):
        for w in range(maxBudget + 1):
            costSekarang = daftarLokasi[i-1]["cost"]
            valueSekarang = daftarLokasi[i-1]["value"]
            if costSekarang <= w:
                dpTable[i][w] = max(dpTable[i-1][w], dpTable[i-1][w-costSekarang] + valueSekarang)
            else:
                dpTable[i][w] = dpTable[i-1][w]
                
    hasilValue = dpTable[n][maxBudget]
    w = maxBudget
    lokasiTerpilih = []
    totalCost = 0
    for i in range(n, 0, -1):
        if hasilValue <= 0: break
        if hasilValue == dpTable[i-1][w]: continue
        else:
            itemTerpilih = daftarLokasi[i-1]
            lokasiTerpilih.append(itemTerpilih)
            hasilValue -= itemTerpilih["value"]
            w -= itemTerpilih["cost"]
            totalCost += itemTerpilih["cost"]
            
    return {"metode": "Dynamic Programming", "lokasiTerpilih": lokasiTerpilih, "totalCost": totalCost, "totalValue": dpTable[n][maxBudget]}

# ==========================================
# ROUTING FLASK & MODUL D (OUTPUT)
# ==========================================
@app.route("/", methods=["GET", "POST"])
def index():
    if request.method == "POST":
        inputBudget = int(request.form["budget"])
        inputJarak = float(request.form["jarak"])
        
        daftarLokasi = load_and_preprocess_data()
        
        # Eksekusi Greedy
        startGreedy = time.perf_counter()
        hasilGreedy = jalankanGreedy(daftarLokasi.copy(), inputBudget, inputJarak)
        waktuGreedy = time.perf_counter() - startGreedy

        # Eksekusi DP
        startDp = time.perf_counter()
        hasilDp = jalankanDp(daftarLokasi.copy(), inputBudget)
        waktuDp = time.perf_counter() - startDp

        # Membuat Grafik Matplotlib
        fig, (ax1, ax2) = plt.subplots(1, 2, figsize=(10, 4))
        
        ax1.bar(["Greedy", "DP"], [hasilGreedy["totalValue"], hasilDp["totalValue"]], color=["#3498db", "#2ecc71"])
        ax1.set_title("Perbandingan Total Value")
        
        ax2.bar(["Greedy", "DP"], [waktuGreedy, waktuDp], color=["#e74c3c", "#f1c40f"])
        ax2.set_title("Waktu Eksekusi (Detik)")
        
        plt.tight_layout()
        
        # Konversi grafik ke format Base64 (tanpa simpan file fisik)
        img = io.BytesIO()
        plt.savefig(img, format="png")
        img.seek(0)
        grafikUrl = base64.b64encode(img.getvalue()).decode()
        plt.close()

        return render_template("index.html", 
                               hasilGreedy=hasilGreedy, 
                               waktuGreedy=round(waktuGreedy, 6),
                               hasilDp=hasilDp, 
                               waktuDp=round(waktuDp, 6),
                               grafikUrl=grafikUrl)

    return render_template("index.html")

@app.route('/kelola_lokasi')
def kelola_lokasi():
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            cursor.execute("SELECT * FROM lokasi")
            data = cursor.fetchall()
            
        for row in data:
            row["latitude"] = float(row["latitude"])
            row["longitude"] = float(row["longitude"])
            
        return render_template('kelola_lokasi.html', data=data)
    except Exception as e:
        print(e)
        return "Error fetching data dari database"
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

@app.route('/add_lokasi', methods=['POST'])
def add_lokasi():
    try:
        nama_daerah = request.form['nama_daerah']
        latitude = float(request.form['latitude'].replace(',', '.'))
        longitude = float(request.form['longitude'].replace(',', '.'))
        biaya_pembangunan = int(request.form['biaya_pembangunan'])
        kepadatan_penduduk = int(request.form['kepadatan_penduduk'])
        daya_beli = int(request.form['daya_beli'])
        
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = "INSERT INTO lokasi (nama_daerah, latitude, longitude, biaya_pembangunan, kepadatan_penduduk, daya_beli) VALUES (%s, %s, %s, %s, %s, %s)"
            cursor.execute(sql, (nama_daerah, latitude, longitude, biaya_pembangunan, kepadatan_penduduk, daya_beli))
        connection.commit()
    except Exception as e:
        print(f"Error add_lokasi: {e}")
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()
    return redirect(url_for('kelola_lokasi'))

@app.route('/edit_lokasi/<int:id>', methods=['POST'])
def edit_lokasi(id):
    try:
        nama_daerah = request.form['nama_daerah']
        latitude = float(request.form['latitude'].replace(',', '.'))
        longitude = float(request.form['longitude'].replace(',', '.'))
        biaya_pembangunan = int(request.form['biaya_pembangunan'])
        kepadatan_penduduk = int(request.form['kepadatan_penduduk'])
        daya_beli = int(request.form['daya_beli'])
        
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = "UPDATE lokasi SET nama_daerah=%s, latitude=%s, longitude=%s, biaya_pembangunan=%s, kepadatan_penduduk=%s, daya_beli=%s WHERE id_lokasi=%s"
            cursor.execute(sql, (nama_daerah, latitude, longitude, biaya_pembangunan, kepadatan_penduduk, daya_beli, id))
        connection.commit()
    except Exception as e:
        print(f"Error edit_lokasi: {e}")
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()
    return redirect(url_for('kelola_lokasi'))

@app.route('/delete_lokasi/<int:id>', methods=['POST'])
def delete_lokasi(id):
    try:
        connection = get_db_connection()
        with connection.cursor() as cursor:
            sql = "DELETE FROM lokasi WHERE id_lokasi=%s"
            cursor.execute(sql, (id,))
        connection.commit()
    except Exception as e:
        print(e)
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()
    return redirect(url_for('kelola_lokasi'))

if __name__ == "__main__":
    app.run(debug=True)