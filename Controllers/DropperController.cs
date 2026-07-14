using DaniGroup.Models;
using Microsoft.AspNetCore.Mvc;

namespace DaniGroup.Controllers
{
    public class DropperController : Controller
    {
        public IActionResult Index()
        {
            var services = new List<ServiceItem>
            {
                new ServiceItem { Title = "Send Parcels", Description = "Fast parcel delivery service." },
                new ServiceItem { Title = "Move Furniture", Description = "Furniture moving for homes and offices." },
                new ServiceItem { Title = "24hr Towing Service", Description = "Emergency towing at any time." },
                new ServiceItem { Title = "Become a Driver", Description = "Apply to join our delivery and towing network." }
            };

            return View(services);
        }
    }
}