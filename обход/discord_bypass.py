import pydivert
import socket
import threading
import time
import logging
import random

# ===== НАСТРОЙКА ЛОГИРОВАНИЯ =====
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(message)s',
    handlers=[
        logging.FileHandler('discord_bypass.log', encoding='utf-8'),
        logging.StreamHandler()
    ]
)
# =================================

DISCORD_DOMAINS = [
    "discord.com",
    "discord.gg",
    "gateway.discord.gg",
    "discordapp.com",
    "discord.media",
    "cdn.discordapp.com",
    "status.discord.com",
]

discord_ips = set()
lock = threading.Lock()

def resolve_discord_ips():
    global discord_ips
    new_ips = set()
    
    logging.info("Резолвим домены Discord...")
    for domain in DISCORD_DOMAINS:
        try:
            ips = socket.getaddrinfo(domain, 443, socket.AF_INET, socket.SOCK_STREAM)
            for ip_info in ips:
                ip = ip_info[4][0]
                new_ips.add(ip)
                logging.info(f"  {domain} -> {ip}")
        except Exception as e:
            logging.error(f"Ошибка резолва {domain}: {e}")
    
    with lock:
        discord_ips = new_ips
    
    logging.info(f"Всего IP Discord: {len(discord_ips)}")
    return new_ips

def ip_updater():
    while True:
        resolve_discord_ips()
        time.sleep(300)

def modify_packet_for_dpi_bypass(packet):
    """
    Модифицирует пакет на лету:
    - Меняет TTL на нестандартный (DPI часто игнорирует пакеты с подозрительным TTL)
    - Добавляет мусор в TCP options если есть место
    """
    raw = bytes(packet.raw)
    payload = bytes(packet.payload) if isinstance(packet.payload, memoryview) else packet.payload
    
    # Размер заголовков
    header_size = len(raw) - len(payload)
    headers = bytearray(raw[:header_size])
    
    # Меняем TTL на случайный между 1 и 64
    # TTL находится на 8-м байте IP-заголовка
    headers[8] = random.randint(1, 64)
    
    # Меняем IP Identification на случайный
    headers[4] = random.randint(0, 255)
    headers[5] = random.randint(0, 255)
    
    # Собираем пакет обратно
    return bytes(headers) + payload

def main():
    global discord_ips
    
    logging.info("="*60)
    logging.info("Discord DPI Bypass — v3 (TTL + IP ID Spoof)")
    logging.info("="*60)
    logging.warning("Запускайте от АДМИНИСТРАТОРА!")
    
    resolve_discord_ips()
    updater = threading.Thread(target=ip_updater, daemon=True)
    updater.start()
    
    logging.info("Запуск перехвата...")
    logging.info("Нажмите Ctrl+C для остановки\n")
    
    win_filter = "outbound and tcp"
    
    processed_connections = set()
    
    try:
        with pydivert.WinDivert(win_filter) as w:
            logging.info("[✓] Перехватчик запущен! Ждём трафик Discord...")
            
            for packet in w:
                if packet.dst_port != 443:
                    w.send(packet)
                    continue
                
                with lock:
                    current_ips = discord_ips.copy()
                
                if packet.dst_addr not in current_ips:
                    w.send(packet)
                    continue
                
                if not packet.payload or len(packet.payload) < 50:
                    w.send(packet)
                    continue
                
                conn_key = f"{packet.src_port}->{packet.dst_addr}:{packet.dst_port}"
                
                # Проверяем, ClientHello ли это
                is_client_hello = False
                payload_bytes = bytes(packet.payload) if isinstance(packet.payload, memoryview) else packet.payload
                content_type = payload_bytes[0] if payload_bytes else 0
                
                if content_type == 0x16 and len(payload_bytes) > 5 and payload_bytes[5] == 0x01:
                    is_client_hello = True
                
                if is_client_hello:
                    # Ищем "discord" в payload
                    has_discord = False
                    for domain in [b"discord.com", b"discord.gg", b"discordapp.com", b"discord.media", b"discord"]:
                        if payload_bytes.find(domain) != -1:
                            has_discord = True
                            break
                    
                    if has_discord and conn_key not in processed_connections:
                        processed_connections.add(conn_key)
                        
                        logging.info(f"[DISCORD ClientHello] {conn_key}")
                        
                        # === ТЕХНИКА: TTL + IP ID Spoof ===
                        # Модифицируем пакет: меняем TTL и IP ID
                        modified_raw = modify_packet_for_dpi_bypass(packet)
                        
                        # Создаём новый пакет с модифицированными заголовками
                        modified_packet = pydivert.Packet(
                            modified_raw,
                            packet.interface,
                            packet.direction
                        )
                        
                        # Отправляем модифицированный пакет вместо оригинального
                        w.send(modified_packet)
                        logging.info(f"    [✓] Пакет отправлен с TTL={modified_raw[8]}, IP_ID={modified_raw[4]:02x}{modified_raw[5]:02x}")
                        continue
                
                # Пропускаем как есть
                w.send(packet)
                
    except KeyboardInterrupt:
        logging.info("\nОстановка... Выход.")
    except Exception as e:
        logging.error(f"Ошибка: {e}")
        import traceback
        traceback.print_exc()

if __name__ == "__main__":
    main()