const windowMs = (parseInt(process.env.RATE_LIMIT_WINDOW_SECONDS, 10) || 60) * 1000;
const maxRequests = parseInt(process.env.RATE_LIMIT_MAX, 10) || 5;

const buckets = new Map();

module.exports = function rateLimit(req, res, next) {
  const now = Date.now();
  const key = req.user?.userId ? `user:${req.user.userId}` : `ip:${req.ip}`;
  const currentBucket = buckets.get(key);

  if (!currentBucket || now - currentBucket.windowStart >= windowMs) {
    buckets.set(key, { count: 1, windowStart: now });
    return next();
  }

  currentBucket.count += 1;

  if (currentBucket.count > maxRequests) {
    const retryAfterSeconds = Math.max(
      1,
      Math.ceil((currentBucket.windowStart + windowMs - now) / 1000)
    );

    const err = new Error("Rate limit exceeded. Please try again later.");
    err.statusCode = 429;
    err.error = "TooManyRequests";
    err.retryAfter = retryAfterSeconds;
    return next(err);
  }

  return next();
};
