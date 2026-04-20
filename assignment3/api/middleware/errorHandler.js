module.exports = function errorHandler(err, req, res, next) {
  const statusCode = Number.isInteger(err.statusCode) ? err.statusCode : 500;
  const error = err.error || defaultErrorCategory(statusCode);
  const message = statusCode >= 500
    ? "An unexpected error occurred."
    : (err.message || defaultMessage(statusCode));
  const requestId = req.requestId || null;
  const timestamp = new Date().toISOString();

  console.error(`Unhandled error for request ${requestId}`, err);

  if (statusCode === 429 && err.retryAfter) {
    res.set("Retry-After", String(err.retryAfter));
  }

  const responseBody = {
    error,
    message,
    statusCode,
    requestId,
    timestamp
  };

  if (statusCode === 429 && err.retryAfter) {
    responseBody.retryAfter = err.retryAfter;
  }

  res.status(statusCode).json(responseBody);
};

function defaultErrorCategory(statusCode) {
  if (statusCode === 400) return "BadRequest";
  if (statusCode === 401) return "Unauthorized";
  if (statusCode === 403) return "Forbidden";
  if (statusCode === 404) return "NotFound";
  if (statusCode === 429) return "TooManyRequests";
  return "InternalServerError";
}

function defaultMessage(statusCode) {
  if (statusCode === 400) return "The request was invalid.";
  if (statusCode === 401) return "Authentication is required or the token is invalid.";
  if (statusCode === 403) return "You do not have permission to access this resource.";
  if (statusCode === 404) return "The requested resource was not found.";
  if (statusCode === 429) return "Rate limit exceeded. Please try again later.";
  return "An unexpected error occurred.";
}
