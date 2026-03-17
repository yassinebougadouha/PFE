"""
Generate sample PDF documents for testing the RAG PDF ingestion.

Creates test PDFs in the app/rag/documents/ folder using PyMuPDF.
"""

import fitz  # PyMuPDF
from pathlib import Path

DOCS_DIR = Path(__file__).parent / "app" / "rag" / "documents"
DOCS_DIR.mkdir(parents=True, exist_ok=True)


def create_pdf(filename: str, title: str, pages: list[str], author: str = "Test Author"):
    """Create a multi-page PDF with the given text content."""
    doc = fitz.open()
    doc.set_metadata({
        "title": title,
        "author": author,
        "subject": "Test Document",
    })

    for page_text in pages:
        page = doc.new_page(width=612, height=792)  # Letter size
        # Insert text with a reasonable font/size
        text_rect = fitz.Rect(50, 50, 562, 742)
        page.insert_textbox(text_rect, page_text, fontsize=11, fontname="helv")

    filepath = DOCS_DIR / filename
    doc.save(str(filepath))
    doc.close()
    print(f"  Created: {filepath} ({filepath.stat().st_size:,} bytes)")
    return filepath


def main():
    print("Generating sample PDFs...")

    # ── PDF 1: Return Policy ──
    create_pdf(
        "return-policy.pdf",
        "Return and Refund Policy",
        [
            (
                "Return and Refund Policy\n\n"
                "Effective Date: January 1, 2025\n\n"
                "1. General Return Policy\n\n"
                "We accept returns within 30 days of purchase for a full refund. "
                "Items must be in their original condition with all tags and packaging intact. "
                "To initiate a return, please contact our customer support team or visit the "
                "Returns section in your account dashboard.\n\n"
                "2. Eligibility Criteria\n\n"
                "The following items are eligible for return:\n"
                "- Unused and unopened products\n"
                "- Products with manufacturing defects\n"
                "- Wrong items received\n"
                "- Damaged items (reported within 48 hours of delivery)\n\n"
                "The following items are NOT eligible for return:\n"
                "- Digital products and software licenses\n"
                "- Customized or personalized items\n"
                "- Perishable goods\n"
                "- Items marked as final sale"
            ),
            (
                "3. Refund Processing\n\n"
                "Once we receive your returned item, our team will inspect it within "
                "3-5 business days. After approval, refunds will be processed to your "
                "original payment method within 5-10 business days.\n\n"
                "4. Exchange Policy\n\n"
                "We offer free exchanges for defective items or wrong sizes. To request "
                "an exchange, contact support with your order number and the desired "
                "replacement item.\n\n"
                "5. Shipping Costs\n\n"
                "Return shipping costs are the responsibility of the customer unless the "
                "return is due to our error (wrong item, defective product). We provide "
                "prepaid return labels for defective items.\n\n"
                "6. Contact Information\n\n"
                "For return inquiries, email returns@example.com or call 1-800-RETURNS "
                "(Monday to Friday, 9 AM to 6 PM EST)."
            ),
        ],
        author="Policy Team",
    )

    # ── PDF 2: Security Best Practices ──
    create_pdf(
        "security-best-practices.pdf",
        "Security Best Practices Guide",
        [
            (
                "Security Best Practices Guide\n\n"
                "Protecting Your Account\n\n"
                "1. Strong Passwords\n\n"
                "Always use a strong, unique password for your account. A good password "
                "should be at least 12 characters long and include a mix of uppercase "
                "letters, lowercase letters, numbers, and special characters. Never reuse "
                "passwords across different services.\n\n"
                "2. Two-Factor Authentication (2FA)\n\n"
                "Enable two-factor authentication for an extra layer of security. We support "
                "authenticator apps (Google Authenticator, Authy) and SMS verification. "
                "Authenticator apps are recommended for better security.\n\n"
                "3. Phishing Awareness\n\n"
                "Be cautious of emails or messages asking for your login credentials. "
                "We will never ask for your password via email. Always verify the sender's "
                "email address and look for HTTPS in the URL before entering credentials."
            ),
            (
                "4. Session Management\n\n"
                "Always log out when using shared or public computers. Regularly review your "
                "active sessions in Settings and revoke any unrecognized sessions.\n\n"
                "5. API Key Security\n\n"
                "If you use API keys, never share them publicly or commit them to version "
                "control repositories. Rotate your API keys regularly and use environment "
                "variables to store them.\n\n"
                "6. Data Backup\n\n"
                "Regularly export and back up your important data. We provide automated "
                "backup options in the Settings panel under Data Management.\n\n"
                "7. Reporting Security Issues\n\n"
                "If you discover a security vulnerability, please report it to "
                "security@example.com. We take all reports seriously and will respond "
                "within 24 hours. Do not publicly disclose vulnerabilities before they "
                "are addressed."
            ),
        ],
        author="Security Team",
    )

    # ── PDF 3: Getting Started / Onboarding ──
    create_pdf(
        "getting-started-guide.pdf",
        "Getting Started - New User Onboarding",
        [
            (
                "Getting Started - New User Onboarding\n\n"
                "Welcome to our platform! This guide will walk you through the initial "
                "setup and help you get the most out of your account.\n\n"
                "Step 1: Account Setup\n\n"
                "After signing up, complete your profile by adding your name, profile "
                "picture, and contact information. This helps our support team assist "
                "you more effectively.\n\n"
                "Step 2: Workspace Configuration\n\n"
                "Create your first workspace by clicking 'New Workspace' on the dashboard. "
                "Workspaces help you organize your projects and collaborate with team members. "
                "You can create multiple workspaces for different teams or projects."
            ),
            (
                "Step 3: Invite Team Members\n\n"
                "Go to Workspace Settings and click 'Invite Members'. Enter their email "
                "addresses and assign roles:\n"
                "- Admin: Full access to all workspace settings and billing\n"
                "- Manager: Can manage projects and team members\n"
                "- Member: Can view and edit assigned projects\n"
                "- Viewer: Read-only access to projects\n\n"
                "Step 4: Create Your First Project\n\n"
                "Click 'New Project' and choose a template or start from scratch. "
                "Add a description, set deadlines, and assign team members.\n\n"
                "Step 5: Explore Integrations\n\n"
                "Connect your favorite tools through our integrations page. We support "
                "Slack, GitHub, Jira, Google Calendar, and many more. Integrations help "
                "streamline your workflow and keep everything in sync."
            ),
            (
                "Step 6: Support Resources\n\n"
                "If you need help at any point:\n\n"
                "- Knowledge Base: Browse our comprehensive help articles at help.example.com\n"
                "- Live Chat: Click the chat icon in the bottom-right corner for instant support\n"
                "- Email: Send questions to support@example.com\n"
                "- Community: Join our community forum at community.example.com\n\n"
                "Tips for Success:\n\n"
                "- Use keyboard shortcuts to work faster (press ? to see the shortcut list)\n"
                "- Set up notifications to stay updated on project changes\n"
                "- Use tags and labels to organize your work\n"
                "- Schedule regular backups of important data\n\n"
                "We are glad to have you on board. If you have any feedback about your "
                "onboarding experience, please let us know at feedback@example.com."
            ),
        ],
        author="Product Team",
    )

    print(f"\nDone! Created 3 sample PDFs in {DOCS_DIR}")


if __name__ == "__main__":
    main()
