using System.Text;
using System.Text.Json;
using DaniGroup.Data;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;

namespace DaniGroup.Controllers
{
    [Route("api/yoco/webhook")]
    [ApiController]
    public class YocoWebhookController : ControllerBase
    {
        private readonly ApplicationDbContext _context;
        private readonly IConfiguration _configuration;

        public YocoWebhookController(ApplicationDbContext context, IConfiguration configuration)
        {
            _context = context;
            _configuration = configuration;
        }

        [HttpPost]
        public async Task<IActionResult> Receive()
        {
            using var reader = new StreamReader(Request.Body, Encoding.UTF8);
            var body = await reader.ReadToEndAsync();

            // IMPORTANT:
            // Replace this with exact Yoco webhook signature verification
            // based on the current Yoco docs/portal for your account.
            // Example header name and verification algorithm may differ.
            var configuredSecret = _configuration["Yoco:WebhookSecret"];

            if (string.IsNullOrWhiteSpace(configuredSecret))
            {
                return BadRequest("Webhook secret not configured.");
            }

            // Parse event body
            using var doc = JsonDocument.Parse(body);
            var root = doc.RootElement;

            // Replace event/property names with exact Yoco webhook schema
            string? eventType = root.TryGetProperty("type", out var typeProp) ? typeProp.GetString() : null;

            if (eventType == "payment.succeeded")
            {
                string? checkoutId = null;

                if (root.TryGetProperty("data", out var dataProp))
                {
                    if (dataProp.TryGetProperty("id", out var idProp))
                    {
                        checkoutId = idProp.GetString();
                    }
                }

                if (!string.IsNullOrWhiteSpace(checkoutId))
                {
                    var order = await _context.Orders.FirstOrDefaultAsync(o => o.PaymentCheckoutId == checkoutId);
                    if (order != null)
                    {
                        order.PaymentStatus = "Paid";
                        order.OrderStatus = "Processing";
                        await _context.SaveChangesAsync();
                    }
                }
            }

            if (eventType == "payment.failed")
            {
                string? checkoutId = null;

                if (root.TryGetProperty("data", out var dataProp))
                {
                    if (dataProp.TryGetProperty("id", out var idProp))
                    {
                        checkoutId = idProp.GetString();
                    }
                }

                if (!string.IsNullOrWhiteSpace(checkoutId))
                {
                    var order = await _context.Orders.FirstOrDefaultAsync(o => o.PaymentCheckoutId == checkoutId);
                    if (order != null)
                    {
                        order.PaymentStatus = "Failed";
                        order.OrderStatus = "Pending Payment";
                        await _context.SaveChangesAsync();
                    }
                }
            }

            return Ok();
        }
    }
}