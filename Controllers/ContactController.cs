using DaniGroup.Data;
using DaniGroup.Models;
using Microsoft.AspNetCore.Mvc;

namespace DaniGroup.Controllers
{
    public class ContactController : Controller
    {
        private readonly ApplicationDbContext _context;

        public ContactController(ApplicationDbContext context)
        {
            _context = context;
        }

        [HttpGet]
        public IActionResult Index()
        {
            return View();
        }

        [HttpPost]
        [ValidateAntiForgeryToken]
        public async Task<IActionResult> Index(ContactMessage model)
        {
            if (!ModelState.IsValid)
            {
                return View(model);
            }

            _context.ContactMessages.Add(model);
            await _context.SaveChangesAsync();

            ViewBag.Success = "Your message has been sent successfully.";
            ModelState.Clear();

            return View(new ContactMessage());
        }
    }
}