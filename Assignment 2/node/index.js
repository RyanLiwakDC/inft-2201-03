import http from "http";
import fs from "fs";
import path from "path";
import jwt from "jsonwebtoken";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const JWT_SECRET = "f94e4ad0-94c6-453d-b2cb-51fe3898c43f-mail-a2";

function sendJson(res, statusCode, payload) {
  res.writeHead(statusCode, { "Content-Type": "application/json" });
  res.end(JSON.stringify(payload));
}

function parseUsersFile() {
  const usersFile = path.join(__dirname, "users.txt");
  const raw = fs.readFileSync(usersFile, "utf8");

  return raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter((line) => line.length > 0)
    .map((line) => {
      const parts = line.split(",").map((part) => part.trim());

      // Support both possible formats:
      // username,password,userId,role
      // userId,username,password,role
      if (parts.length !== 4) {
        return null;
      }

      if (/^\d+$/.test(parts[0])) {
        const [userId, username, password, role] = parts;
        return {
          userId: Number(userId),
          username,
          password,
          role,
        };
      }

      const [username, password, userId, role] = parts;
      return {
        userId: Number(userId),
        username,
        password,
        role,
      };
    })
    .filter(Boolean);
}

http
  .createServer((req, res) => {
    if (req.method === "POST" && req.url === "/login") {
      let body = "";

      req.on("data", (chunk) => {
        body += chunk;
      });

      req.on("end", () => {
        let parsedBody;

        try {
          parsedBody = JSON.parse(body || "{}");
        } catch {
          return sendJson(res, 400, { error: "Invalid JSON." });
        }

        try {
          const { username, password } = parsedBody;

          if (!username || !password) {
            return sendJson(res, 400, { error: "Username and password are required." });
          }

          const users = parseUsersFile();
          const matchedUser = users.find(
            (user) => user.username === username && user.password === password
          );

          if (!matchedUser) {
            return sendJson(res, 401, { error: "Invalid credentials." });
          }

          const token = jwt.sign(
            {
              userId: matchedUser.userId,
              role: matchedUser.role,
            },
            JWT_SECRET,
            { expiresIn: "1h" }
          );

          return sendJson(res, 200, { token });
        } catch (err) {
          console.error(err);
          return sendJson(res, 500, { error: "Server error." });
        }
      });

      return;
    }

    if (req.method === "GET") {
      return sendJson(res, 200, { message: "Node auth service running." });
    }

    return sendJson(res, 404, { error: "Not found." });
  })
  .listen(8000);

console.log("listening on port 8000");
