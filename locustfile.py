from locust import HttpUser, task, between, SequentialTaskSet
import random

# ============================================================
# SIMONEV Performance Testing Script
# Endpoint yang diuji:
# 1. POST /login
# 2. POST /register
# 3. GET /anak
# 4. POST /observasi/batch
# 5. GET /pengumuman
# ============================================================

BASE_URL = "http://127.0.0.1:8000/api"

def generate_user(user_id):
    return {
        "role": "guru",
        "nama_lengkap": f"Penguji User {user_id}",
        "username": f"penguji{user_id}",
        "email": f"penguji{user_id}@gmail.com",
        "no_hp": f"08120000{user_id:04d}",
        "password": "password123",
        "password_confirmation": "password123",
        "nik": f"3171{user_id:016d}"[:16],
        "nip": f"1234{user_id:016d}"[:16],
        "jabatan": "Guru Kelas",
        "tanggal_lahir": "1990-01-01",
        "alamat": f"Jl. Testing No. {user_id}",
        "jenis_kelamin": "Perempuan",
    }


class SimonevTaskSet(SequentialTaskSet):

    token = None
    user_counter = 0

    def on_start(self):
        SimonevTaskSet.user_counter += 1
        self.user_id = SimonevTaskSet.user_counter
        self.login()

    def login(self):
        with self.client.post(
            f"{BASE_URL}/login",
            json={
                "username": "admin",
                "password": "admin123"
            },
            catch_response=True,
            name="POST /login"
        ) as response:
            if response.status_code == 200:
                data = response.json()
                self.token = data.get("token") or (
                    data.get("data", {}).get("token") if isinstance(data.get("data"), dict) else None
                )
                if self.token:
                    response.success()
                else:
                    response.failure("Token tidak ditemukan di response")
            else:
                response.failure(f"Login gagal: {response.status_code}")

    def get_headers(self):
        return {
            "Authorization": f"Bearer {self.token}",
            "Content-Type": "application/json",
            "Accept": "application/json",
        }

    @task
    def registrasi(self):
        """POST /register - Registrasi akun baru"""
        payload = generate_user(self.user_id * 1000 + random.randint(1, 9999))
        with self.client.post(
            f"{BASE_URL}/register",
            json=payload,
            catch_response=True,
            name="POST /register"
        ) as response:
            if response.status_code in [200, 201, 422]:
                response.success()
            else:
                response.failure(f"Registrasi gagal: {response.status_code}")

    @task
    def lihat_data_anak(self):
        """GET /anak - Melihat daftar data anak"""
        if not self.token:
            return

        with self.client.get(
            f"{BASE_URL}/anak",
            headers=self.get_headers(),
            catch_response=True,
            name="GET /anak"
        ) as response:
            if response.status_code == 200:
                response.success()
            else:
                response.failure(f"Gagal ambil data anak: {response.status_code}")

    @task
    def input_penilaian(self):
        """POST /observasi/batch - Input penilaian perkembangan anak"""
        if not self.token:
            return

        with self.client.get(
            f"{BASE_URL}/anak",
            headers=self.get_headers(),
            catch_response=True,
            name="GET /anak"
        ) as response:
            if response.status_code == 200:
                data = response.json()
                anak_list = data.get("data", [])
                if anak_list:
                    id_anak = anak_list[0].get("id")

                    with self.client.get(
                        f"{BASE_URL}/indikator",
                        headers=self.get_headers(),
                        catch_response=True,
                        name="GET /indikator"
                    ) as res_indikator:
                        if res_indikator.status_code == 200:
                            indikator_list = res_indikator.json().get("data", [])
                            if indikator_list and id_anak:
                                indikator = indikator_list[0]
                                payload = {
                                    "id_anak": id_anak,
                                    "tahun_ajaran": "2025/2026",
                                    "semester": "2",
                                    "tanggal_observasi": "2026-06-18",
                                    "komentar": "Anak menunjukkan perkembangan yang baik.",
                                    "penilaian": [
                                        {
                                            "id_indikator": indikator.get("id"),
                                            "nilai": random.choice(["BB", "MB", "BSH", "BSB"])
                                        }
                                    ]
                                }
                                with self.client.post(
                                    f"{BASE_URL}/observasi/batch",
                                    json=payload,
                                    headers=self.get_headers(),
                                    catch_response=True,
                                    name="POST /observasi/batch"
                                ) as res_obs:
                                    if res_obs.status_code in [200, 201]:
                                        res_obs.success()
                                    else:
                                        res_obs.failure(f"Input penilaian gagal: {res_obs.status_code}")
            else:
                response.failure(f"Gagal ambil data anak: {response.status_code}")

    @task
    def lihat_pengumuman(self):
        """GET /pengumuman - Melihat daftar pengumuman"""
        with self.client.get(
            f"{BASE_URL}/pengumuman",
            catch_response=True,
            name="GET /pengumuman"
        ) as response:
            if response.status_code == 200:
                response.success()
            else:
                response.failure(f"Gagal ambil pengumuman: {response.status_code}")


class SimonevUser(HttpUser):
    tasks = [SimonevTaskSet]
    wait_time = between(1, 3)
    host = "http://127.0.0.1:8000"
