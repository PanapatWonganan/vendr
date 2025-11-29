# Setup GitHub SSH Key บน Server

## บน Server (Vultr):

```bash
# 1. สร้าง SSH key
ssh-keygen -t ed25519 -C "your_email@example.com"
# กด Enter 3 ครั้ง (ไม่ต้องใส่ passphrase)

# 2. แสดง public key
cat ~/.ssh/id_ed25519.pub
# Copy ทั้งหมด (ขึ้นต้นด้วย ssh-ed25519)
```

## บน GitHub:

1. ไปที่ https://github.com/settings/keys
2. คลิก "New SSH key"
3. Title: "Vultr Production Server"
4. Key: Paste public key ที่ copy มา
5. คลิก "Add SSH key"

## กลับมาที่ Server:

```bash
# ทดสอบ connection
ssh -T git@github.com
# ถ้าถาม yes/no ให้พิมพ์ yes

# Clone ด้วย SSH URL
git clone git@github.com:PanapatWonganan/vendr.git
```

✅ เสร็จ!
