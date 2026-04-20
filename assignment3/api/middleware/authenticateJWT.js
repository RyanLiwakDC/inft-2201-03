const jwt = require("jsonwebtoken");

const SECRET = process.env.JWT_SECRET || "supersecretkey";

module.exports = function authenticateJWT(req, res, next) {
  const authHeader = req.headers.authorization;

  if (!authHeader || !authHeader.startsWith("Bearer ")) {
    const err = new Error("Missing or invalid Authorization header.");
    err.statusCode = 401;
    err.error = "Unauthorized";
    return next(err);
  }

  const token = authHeader.slice("Bearer ".length).trim();

  if (!token) {
    const err = new Error("Missing bearer token.");
    err.statusCode = 401;
    err.error = "Unauthorized";
    return next(err);
  }

  try {
    const decoded = jwt.verify(token, SECRET);
    req.user = decoded;
    return next();
  } catch (verifyError) {
    const err = new Error(
      verifyError.name === "TokenExpiredError"
        ? "Token has expired."
        : "Invalid token."
    );
    err.statusCode = 401;
    err.error = "Unauthorized";
    return next(err);
  }
};
