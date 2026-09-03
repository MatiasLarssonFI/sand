#include <algorithm>
#include <chrono>
#include <iostream>
#include <random>
#include <string>
#include <vector>

int main() {
    const std::vector<std::string> tips = {
        "Be curious: ask people about themselves and listen closely.",
        "Be kind: small consistent kindness is more memorable than trying too hard.",
        "Be reliable: do what you said you'd do.",
        "Be positive: avoid gossip and let your energy be calm.",
        "Be yourself: confidence is more attractive than pretending.",
        "Be generous with compliments, but keep them sincere.",
    };

    const auto now = std::chrono::system_clock::now().time_since_epoch().count();
    std::mt19937 rng(static_cast<unsigned int>(now));
    std::uniform_int_distribution<std::size_t> pick(0, tips.size() - 1);

    std::cout << "You cannot force people to like you, but you can become\n";
    std::cout << "someone people enjoy being around.\n\n";
    std::cout << "Today's focus:\n";
    std::cout << "- " << tips[pick(rng)] << '\n';
    std::cout << "- Smile, make eye contact, and say hello first.\n";
    std::cout << "- Leave conversations a little better than you found them.\n";
    std::cout << "\nKeep it simple: kindness + confidence + consistency.\n";

    return 0;
}
