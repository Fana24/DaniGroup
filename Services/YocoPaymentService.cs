using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using DaniGroup.Models;
using Microsoft.Extensions.Options;

namespace DaniGroup.Services
{
    public class YocoPaymentService
    {
        private readonly HttpClient _httpClient;
        private readonly YocoSettings _settings;

        public YocoPaymentService(HttpClient httpClient, IOptions<YocoSettings> options)
        {
            _httpClient = httpClient;
            _settings = options.Value;
        }

        public async Task<YocoCreateCheckoutResponse> CreateCheckoutAsync(Order order)
        {
            // IMPORTANT:
            // The payload and response field names below must be checked against your current Yoco developer docs.
            // Public docs confirm online checkout resources and Bearer-auth API usage on payments.yoco.com,
            // but you should verify the exact create-checkout schema in your Yoco portal/docs.
            // This app structure is correct; this is the one place where exact field names may need adjustment.

            var payload = new
            {
                amount = (int)(order.TotalAmount * 100), // cents
                currency = "ZAR",
                successUrl = $"{_settings.SuccessUrl}?orderId={order.Id}",
                cancelUrl = $"{_settings.CancelUrl}?orderId={order.Id}",
                metadata = new
                {
                    orderId = order.Id,
                    email = order.Email,
                    provider = "DaniGroup"
                }
            };

            var json = JsonSerializer.Serialize(payload);

            var request = new HttpRequestMessage(HttpMethod.Post, $"{_settings.ApiBaseUrl}/checkouts")
            {
                Content = new StringContent(json, Encoding.UTF8, "application/json")
            };

            request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", _settings.SecretKey);

            var response = await _httpClient.SendAsync(request);
            var responseText = await response.Content.ReadAsStringAsync();

            if (!response.IsSuccessStatusCode)
            {
                return new YocoCreateCheckoutResponse
                {
                    ErrorMessage = $"Yoco error: {response.StatusCode} - {responseText}"
                };
            }

            using var doc = JsonDocument.Parse(responseText);

            string? checkoutId = null;
            string? redirectUrl = null;
            string? status = null;

            if (doc.RootElement.TryGetProperty("id", out var idProp))
            {
                checkoutId = idProp.GetString();
            }

            if (doc.RootElement.TryGetProperty("redirectUrl", out var redirectProp))
            {
                redirectUrl = redirectProp.GetString();
            }
            else if (doc.RootElement.TryGetProperty("redirect_url", out var redirectSnakeProp))
            {
                redirectUrl = redirectSnakeProp.GetString();
            }

            if (doc.RootElement.TryGetProperty("status", out var statusProp))
            {
                status = statusProp.GetString();
            }

            return new YocoCreateCheckoutResponse
            {
                Id = checkoutId,
                RedirectUrl = redirectUrl,
                Status = status
            };
        }
    }
}