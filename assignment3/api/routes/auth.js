const express = require("express");
const jwt = require("jsonwebtoken");
const users = require("../data/users");

const router = express.Router();
const SECRET = process.env.JWT_SECRET || "CHANGE_ME_BEFORE_SUBMISSION";

router.post("/login", (req, res, next) => {
  const { username, password } = req.body || {};

  if (!username || !password) {
    const err = new Error("Username and password are required.");
    err.statusCode = 400;
    err.error = "BadRequest";
    return next(err);
  }

  const user = users.find(
    (candidate) => candidate.username === username && candidate.password === password
  );

  if (!user) {
    const err = new Error("Invalid username or password.");
    err.statusCode = 401;
    err.error = "Unauthorized";
    return next(err);
  }

  const token = jwt.sign(
    {
      userId: user.id,
      role: user.role
    },
    SECRET,
    { expiresIn: "1h" }
  );

  res.json({ token });
});

module.exports = router;
