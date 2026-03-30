const express = require('express');
const path = require('path');
const PORT = process.env.PORT || 5000;

const mysql = require("mysql2");

const db = mysql.createPool({
    host: "thsv63.hostatom.com",
    user: "google_student",
    password: "orapimwit",
    database: "google_student",
    port: 3306,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
    enableKeepAlive: true, // ช่วยรักษาการเชื่อมต่อให้ไม่หลุดง่าย
    keepAliveInitialDelay: 10000
});

// ไม่ต้องใช้ db.connect() แล้วครับ Pool จะเชื่อมต่อให้เองเมื่อมีการ Query
console.log("MySQL Pool Created");



const app = express();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'public')));

app.get('/', (req, res) => {
    res.render('search')
});

app.get('/moneying/:id', (req, res) => {
        const id = req.params.id

        const sql = "SELECT * FROM stdreport WHERE id = ?"

        db.query(sql,[id],(err,result)=>{

        const std = result[0]

        res.render("moneying",{std})

    });
});

app.post('/addmoney', (req, res) => {
    // 1. นับจำนวนแถวก่อนเพื่อสร้างเลขที่ (no)
    const sqlCount = "SELECT COUNT(*) AS total FROM stdmoney";

    db.query(sqlCount, (err, result) => {
        if (err) {
            console.error(err);
            return res.status(500).send("Database Error");
        }

        // ดึงค่า total ออกมา (ใช้ result[0].total)
        let rowCount = result[0].total; 
        
        // สร้าง format เลขที่ เช่น 001-2569, 002-2569
        // ใช้ .toString().padStart(3, '0') เพื่อให้เป็น 001, 002
        const formattedNumber = (rowCount + 1).toString().padStart(3, '0');
        const no = `${formattedNumber}-2569`;

        const now = new Date();
        const d = String(now.getDate()).padStart(2, '0');
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const y = now.getFullYear() + 543;
        const h = String(now.getHours()).padStart(2, '0');
        const min = String(now.getMinutes()).padStart(2, '0');
        
        const fullDate = `${d}-${m}-${y}-${h}:${min}`;
        const datemoney = `${y}-${m}-${d}`;
        const timemoney = `${h}:${min}`

        const {id, name, classs, homestd, pk, bag, polo, sport, market, bank, psis, damage, moneysum, state } = req.body;

        // 2. เตรียม SQL (แนะนำให้เอา id ออกจากรายการ INSERT ถ้า id เป็น Auto Increment)
        const sql = `INSERT INTO stdmoney 
                    (id, name, classs, homestd, pk, bag, polo, sport, market, bank, psis, damage, moneysum, state, no, datemoney, time) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`;

        // สังเกตว่าผมตัด id ออกจาก values array ด้วย
        const values = [id, name, classs, homestd, pk, bag, polo, sport, market, bank, psis, damage, moneysum, state, no, datemoney, timemoney];

        // 3. รันคำสั่งบันทึกข้อมูล
        db.query(sql, values, (err, result) => {
            if (err) {
                console.error("Insert Error:", err);
                return res.status(500).send("เกิดข้อผิดพลาดในการบันทึกข้อมูล");
            }
            
            res.send(`
                <script>
                    alert("ซื้อสำเร็จ! เลขที่บันทึกคือ: ${no}");
                    window.location.href = "/showmoneystd/${id}";
                </script>
            `);

        });
    });
});


app.get('/showmoneystd/:id', (req, res) => {
        const id = req.params.id

        const sql = "SELECT * FROM stdmoney WHERE id = ?"

        db.query(sql,[id],(err,result)=>{

        const std = result[0]

        res.render("showmoneystd",{std})

    });
});

app.listen(PORT, () => {
    console.log(`Server is running on port localhost:${PORT}`);
});