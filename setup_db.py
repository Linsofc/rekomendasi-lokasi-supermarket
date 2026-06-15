import pymysql
import csv
import os

def setup_database():
    try:
        # Hubungkan ke MySQL server (tanpa specify database dulu)
        connection = pymysql.connect(
            host='localhost',
            user='root',
            password='',
            cursorclass=pymysql.cursors.DictCursor
        )
        
        with connection.cursor() as cursor:
            # Buat database jika belum ada
            cursor.execute("CREATE DATABASE IF NOT EXISTS db_lokasi")
            print("Database 'db_lokasi' berhasil dibuat atau sudah ada.")
            
            # Gunakan database tersebut
            cursor.execute("USE db_lokasi")
            
            # Buat tabel lokasi
            create_table_sql = """
            CREATE TABLE IF NOT EXISTS lokasi (
                id_lokasi INT AUTO_INCREMENT PRIMARY KEY,
                nama_daerah VARCHAR(255) NOT NULL,
                latitude DECIMAL(10, 6) NOT NULL,
                longitude DECIMAL(10, 6) NOT NULL,
                biaya_pembangunan INT NOT NULL,
                kepadatan_penduduk INT NOT NULL,
                daya_beli INT NOT NULL
            )
            """
            cursor.execute(create_table_sql)
            print("Tabel 'lokasi' berhasil dibuat atau sudah ada.")
            
            # Cek apakah tabel kosong
            cursor.execute("SELECT COUNT(*) as count FROM lokasi")
            result = cursor.fetchone()
            
            if result['count'] == 0:
                print("Tabel masih kosong, mulai mengimpor data dari CSV...")
                csv_file = 'data_lokasi.csv'
                
                if os.path.exists(csv_file):
                    with open(csv_file, mode='r', encoding='utf-8') as file:
                        csv_reader = csv.DictReader(file)
                        insert_query = """
                        INSERT INTO lokasi (nama_daerah, latitude, longitude, biaya_pembangunan, kepadatan_penduduk, daya_beli)
                        VALUES (%s, %s, %s, %s, %s, %s)
                        """
                        for row in csv_reader:
                            cursor.execute(insert_query, (
                                row['nama_daerah'],
                                float(row['latitude']),
                                float(row['longitude']),
                                int(row['biaya_pembangunan']),
                                int(row['kepadatan_penduduk']),
                                int(row['daya_beli'])
                            ))
                    connection.commit()
                    print("Data dari CSV berhasil diimpor.")
                else:
                    print(f"File {csv_file} tidak ditemukan!")
            else:
                print("Tabel 'lokasi' sudah berisi data. Lewati proses impor.")
                
    except pymysql.MySQLError as e:
        print(f"Error saat konfigurasi database: {e}")
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

if __name__ == '__main__':
    setup_database()
